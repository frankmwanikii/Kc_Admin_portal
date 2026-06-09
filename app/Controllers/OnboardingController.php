<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\View;
use App\Services\OnboardingService;
use App\Services\QrService;

class OnboardingController
{
    public function show(string $token): void
    {
        if (!$this->validQr($token)) {
            View::render('onboarding/invalid', ['title' => 'Invalid QR Code'], 'layouts/guest');
            return;
        }

        View::render('onboarding/form', $this->formViewData($token), 'layouts/guest');
    }

    public function submit(string $token): void
    {
        if (!$this->validQr($token)) {
            View::render('onboarding/invalid', ['title' => 'Invalid QR Code'], 'layouts/guest');
            return;
        }

        $data = $this->parseSubmission();

        if ($error = $this->validateSubmission($data)) {
            View::render('onboarding/form', $this->formViewData($token, $data, $error), 'layouts/guest');
            return;
        }

        try {
            $service = new OnboardingService();
            $service->createMember($data, $token);
            View::render('onboarding/success', [
                'title' => 'Welcome!',
                'email' => $data['email'],
                'churchName' => $_ENV['CHURCH_NAME'] ?? 'Kingdomcity church',
            ], 'layouts/guest');
        } catch (\Throwable $e) {
            View::render('onboarding/form', $this->formViewData(
                $token,
                $data,
                'Registration failed. This email may already be registered.'
            ), 'layouts/guest');
        }
    }

    public function qrDisplay(): void
    {
        $token = 'church-onboard-2026';
        $qr = new QrService();
        $url = $qr->onboardingUrl($token);
        View::render('admin/qr-code', [
            'title' => 'Onboarding QR Code',
            'qrDataUri' => $qr->generateDataUri($url),
            'url' => $url,
        ], 'layouts/admin');
    }

    private function validQr(string $token): bool
    {
        $stmt = Database::connection()->prepare('SELECT id FROM onboarding_qr_codes WHERE token = ? AND is_active = 1');
        $stmt->execute([$token]);

        return (bool) $stmt->fetch();
    }

    /** @return array<string, mixed> */
    private function parseSubmission(): array
    {
        $children = [];
        $childNames = $_POST['children_name'] ?? [];
        $childAges = $_POST['children_age'] ?? [];
        if (is_array($childNames)) {
            foreach ($childNames as $i => $name) {
                $name = trim((string) $name);
                if ($name === '') {
                    continue;
                }
                $ageRaw = $childAges[$i] ?? '';
                $children[] = [
                    'name' => $name,
                    'age' => $ageRaw !== '' ? (int) $ageRaw : null,
                ];
            }
        }

        $ministry = $_POST['ministry_interests'] ?? [];
        if (is_array($ministry)) {
            $ministry = implode(', ', array_filter(array_map('trim', $ministry)));
        }

        $maritalStatus = $_POST['marital_status'] ?? '';
        $allowedMarital = ['single', 'married', 'divorced', 'prefer_not_to_say'];

        return [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'gender' => $_POST['gender'] ?? null,
            'date_of_birth' => $_POST['date_of_birth'] ?: null,
            'marital_status' => in_array($maritalStatus, $allowedMarital, true) ? $maritalStatus : null,
            'spouse_name' => trim($_POST['spouse_name'] ?? ''),
            'residence' => trim($_POST['residence'] ?? ''),
            'city' => trim($_POST['city'] ?? ''),
            'county' => trim($_POST['county'] ?? ''),
            'household_name' => trim($_POST['household_name'] ?? ''),
            'address' => trim($_POST['residence'] ?? ''),
            'is_head' => isset($_POST['is_head']),
            'children' => $children,
            'baptized' => isset($_POST['baptized']),
            'wish_to_be_baptized' => isset($_POST['wish_to_be_baptized']),
            'baptism_date' => $_POST['baptism_date'] ?: null,
            'previous_church' => trim($_POST['previous_church'] ?? ''),
            'how_heard_about_us' => trim($_POST['how_heard_about_us'] ?? ''),
            'ministry_interests' => (string) $ministry,
            'occupation' => trim($_POST['occupation'] ?? ''),
            'employer' => trim($_POST['employer'] ?? ''),
            'skills_talents' => trim($_POST['skills_talents'] ?? ''),
            'emergency_contact_name' => trim($_POST['emergency_contact_name'] ?? ''),
            'emergency_contact_phone' => trim($_POST['emergency_contact_phone'] ?? ''),
            'member_notes' => trim($_POST['member_notes'] ?? ''),
        ];
    }

    /** @param array<string, mixed> $data */
    private function validateSubmission(array $data): ?string
    {
        if (!$data['first_name'] || !$data['last_name'] || !$data['email']) {
            return 'Please fill in your name and email.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }

        if (!$data['phone']) {
            return 'Please provide a phone number so we can reach you.';
        }

        if (!$data['residence'] || !$data['city']) {
            return 'Please tell us where you live (residence and city).';
        }

        if (!$data['marital_status']) {
            return 'Please select your marital status.';
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    private function formViewData(string $token, array $data = [], ?string $error = null): array
    {
        return [
            'title' => 'Join Our Church Family',
            'token' => $token,
            'churchName' => $_ENV['CHURCH_NAME'] ?? 'Kingdomcity church',
            'slogan' => 'Transformed lives',
            'error' => $error,
            'data' => $data,
        ];
    }
}
