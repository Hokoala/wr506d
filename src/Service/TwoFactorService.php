<?php
// src/Service/TwoFactorService.php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use OTPHP\TOTP;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

class TwoFactorService
{
    private string $issuer;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        string $appName = 'WR506D'
    ) {
        $this->entityManager = $entityManager;
        $this->issuer = $appName;
    }

    // ============================================
    // GÉNÉRATION DU SECRET
    // ============================================

    /**
     * Generate a new TOTP secret
     * Format: Base32 encodé (ex: JBSWY3DPEHPK3PXP)
     */
    public function generateSecret(): string
    {
        $totp = TOTP::generate();
        return $totp->getSecret();
    }

    // ============================================
    // TOTP INSTANCE
    // ============================================

    /**
     * Get TOTP instance for a user
     */
    private function getTOTP(User $user): TOTP
    {
        $secret = $user->getTwoFactorSecret();

        if ($secret === null) {
            throw new \RuntimeException('User does not have a 2FA secret');
        }

        $totp = TOTP::createFromSecret($secret);
        $totp->setLabel($user->getEmail() ?? 'user');
        $totp->setIssuer($this->issuer);
        $totp->setPeriod(30);

        return $totp;
    }

    // ============================================
    // PROVISIONING URI
    // ============================================

    /**
     * Generate provisioning URI for QR code
     */
    public function getProvisioningUri(User $user): string
    {
        return $this->getTOTP($user)->getProvisioningUri();
    }

    // ============================================
    // QR CODE (CORRIGÉ)
    // ============================================

    /**
     * Generate QR code as base64 image data
     */
    public function getQrCode(User $user): string
    {
        $provisioningUri = $this->getProvisioningUri($user);

        // Nouvelle syntaxe pour endroid/qr-code v4+
        $qrCode = new QrCode($provisioningUri);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return 'data:image/png;base64,' . base64_encode($result->getString());
    }

    // ============================================
    // VÉRIFICATION DU CODE TOTP (6 chiffres)
    // ============================================

    /**
     * Verify a TOTP code (6 digits)
     */
    public function verifyCode(User $user, string $code): bool
    {
        if (!$user->getTwoFactorSecret()) {
            return false;
        }

        $code = preg_replace('/\s+/', '', $code);

        if (!preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $totp = $this->getTOTP($user);

        return $totp->verify($code, null, 1);
    }

    // ============================================
    // CODES DE SECOURS (8 caractères)
    // ============================================

    /**
     * Generate 8 backup codes
     */
    public function generateBackupCodes(User $user): array
    {
        $codes = [];
        $hashedCodes = [];

        for ($i = 0; $i < 8; $i++) {
            $code = $this->generateAlphanumericCode(8);
            $codes[] = $code;
            $hashedCodes[] = password_hash($code, PASSWORD_DEFAULT);
        }

        $user->setTwoFactorBackupCodes($hashedCodes);
        $this->entityManager->flush();

        return $codes;
    }

    private function generateAlphanumericCode(int $length): string
    {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $code;
    }

    /**
     * Verify a backup code
     */
    public function verifyBackupCode(User $user, string $code): bool
    {
        $hashedCodes = $user->getTwoFactorBackupCodes();

        if (!$hashedCodes || empty($hashedCodes)) {
            return false;
        }

        $code = strtoupper(preg_replace('/[\s\-]/', '', $code));

        if (!preg_match('/^[A-Z0-9]{8}$/', $code)) {
            return false;
        }

        foreach ($hashedCodes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                unset($hashedCodes[$index]);
                $user->setTwoFactorBackupCodes(array_values($hashedCodes));
                $this->entityManager->flush();
                return true;
            }
        }

        return false;
    }

    /**
     * Get remaining backup codes count
     */
    public function getRemainingBackupCodesCount(User $user): int
    {
        $codes = $user->getTwoFactorBackupCodes();
        return $codes ? count($codes) : 0;
    }
}
