<?php
// src/Controller/TwoFactorController.php

namespace App\Controller;

use App\Entity\User;
use App\Service\TwoFactorService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/2fa')]
class TwoFactorController extends AbstractController
{
    public function __construct(
        private TwoFactorService $twoFactorService,
        private EntityManagerInterface $entityManager
    ) {}

    // ============================================
    // POST /api/2fa/setup
    // Génère le secret et le QR code
    // ============================================

    #[Route('/setup', name: 'app_2fa_setup', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function setup(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        // Générer le secret
        $secret = $this->twoFactorService->generateSecret();

        // Stocker le secret
        $user->setTwoFactorSecret($secret);

        // ON FORCE A FALSE !
        $user->setTwoFactorEnabled(false);

        $this->entityManager->flush();

        // QR code
        $qrCodeDataUri = $this->twoFactorService->getQrCode($user);

        // URL de provision générée par le module OTP
        $provisioningUri = $this->twoFactorService->getProvisioningUri($user);

        return $this->json([
            'secret' => $secret,
            'qr_code' => $qrCodeDataUri,
            'provisioning_uri' => $provisioningUri,
            'message' => 'Scan this QR code with your authenticator app...',
        ]);
    }

    // ============================================
    // GET /api/2fa/status
    // Récupère le statut 2FA
    // ============================================

    #[Route('/status', name: 'app_2fa_status', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function getStatus(): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        return $this->json([
            'enabled' => $user->isTwoFactorEnabled(),
            'has_secret' => $user->getTwoFactorSecret() !== null,
            'backup_codes_remaining' => $this->twoFactorService->getRemainingBackupCodesCount($user),
        ]);
    }

    // ============================================
    // POST /api/2fa/enable
    // Active le 2FA après vérification du code
    // Body: { "code": "123456" }
    // ============================================

    #[Route('/enable', name: 'app_2fa_enable', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function enable(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';

        if (empty($code)) {
            return $this->json(['error' => 'Code is required'], 400);
        }

        // Vérifier que le setup a été fait
        if (!$user->getTwoFactorSecret()) {
            return $this->json(['error' => 'Please call /api/2fa/setup first'], 400);
        }

        // Vérifier le code TOTP
        if (!$this->twoFactorService->verifyCode($user, $code)) {
            return $this->json(['error' => 'Invalid code'], 400);
        }

        // Activer le 2FA
        $user->setTwoFactorEnabled(true);

        // Générer 8 codes de secours
        $backupCodes = $this->twoFactorService->generateBackupCodes($user);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => '2FA enabled successfully!',
            'backup_codes' => $backupCodes,
            'warning' => 'IMPORTANT: Save these backup codes. They will not be shown again.'
        ]);
    }

    // ============================================
    // POST /api/2fa/disable
    // Désactive le 2FA
    // Body: { "code": "123456", "password": "motdepasse" }
    // ============================================

    #[Route('/disable', name: 'app_2fa_disable', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function disable(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';
        $password = $data['password'] ?? '';

        // Vérifier le mot de passe
        if (!password_verify($password, $user->getPassword())) {
            return $this->json(['error' => 'Invalid password'], 400);
        }

        // Vérifier le code 2FA
        if (!$this->twoFactorService->verifyCode($user, $code)) {
            return $this->json(['error' => 'Invalid 2FA code'], 400);
        }

        // Désactiver le 2FA
        $user->setTwoFactorEnabled(false);
        $user->setTwoFactorSecret(null);
        $user->setTwoFactorBackupCodes(null);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => '2FA disabled successfully'
        ]);
    }

    // ============================================
    // POST /api/2fa/verify
    // Vérifie un code 2FA
    // Body: { "code": "123456", "isBackupCode": false }
    // ============================================

    #[Route('/verify', name: 'app_2fa_verify', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function verify(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';
        $isBackupCode = $data['isBackupCode'] ?? false;

        if (empty($code)) {
            return $this->json(['error' => 'Code is required'], 400);
        }

        // Vérifier le code
        $isValid = $isBackupCode
            ? $this->twoFactorService->verifyBackupCode($user, $code)
            : $this->twoFactorService->verifyCode($user, $code);

        if (!$isValid) {
            return $this->json(['error' => 'Invalid code'], 400);
        }

        return $this->json([
            'success' => true,
            'message' => 'Code verified successfully'
        ]);
    }

    // ============================================
    // POST /api/2fa/backup-codes/regenerate
    // Régénère les codes de secours
    // Body: { "code": "123456" }
    // ============================================

    #[Route('/backup-codes/regenerate', name: 'app_2fa_regenerate_backup', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function regenerateBackupCodes(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->json(['error' => 'User not found'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $code = $data['code'] ?? '';

        // Vérifier le code 2FA
        if (!$this->twoFactorService->verifyCode($user, $code)) {
            return $this->json(['error' => 'Invalid 2FA code'], 400);
        }

        // Régénérer les codes
        $backupCodes = $this->twoFactorService->generateBackupCodes($user);

        $this->entityManager->flush();

        return $this->json([
            'success' => true,
            'backup_codes' => $backupCodes,
            'warning' => 'Old backup codes have been invalidated.'
        ]);
    }
}
