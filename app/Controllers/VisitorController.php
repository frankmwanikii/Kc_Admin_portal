<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\View;
use App\Services\SettingsService;
use App\Services\VisitorService;

class VisitorController
{
    public function show(): void
    {
        if (Auth::check()) {
            View::redirect(Auth::isAdmin() ? '/admin' : '/portal');
        }

        View::render('visitor/form', $this->formViewData(), 'layouts/guest');
    }

    public function submit(): void
    {
        if (Auth::check()) {
            View::redirect(Auth::isAdmin() ? '/admin' : '/portal');
        }

        $data = $this->parseSubmission();

        if ($error = $this->validateSubmission($data)) {
            View::render('visitor/form', $this->formViewData($data, $error), 'layouts/guest');
            return;
        }

        try {
            VisitorService::create($data);
            View::render('visitor/success', [
                'title' => 'Thank You!',
                'churchName' => SettingsService::churchName(),
                'email' => $data['email'],
            ], 'layouts/guest');
        } catch (\Throwable) {
            View::render('visitor/form', $this->formViewData(
                $data,
                'Something went wrong. Please try again in a moment.'
            ), 'layouts/guest');
        }
    }

    /** @return array<string, mixed> */
    private function parseSubmission(): array
    {
        $children = [];
        $childNames = $_POST['children_name'] ?? [];
        if (is_array($childNames)) {
            foreach ($childNames as $name) {
                $name = trim((string) $name);
                if ($name !== '') {
                    $children[] = $name;
                }
            }
        }

        return [
            'first_name' => trim($_POST['first_name'] ?? ''),
            'last_name' => trim($_POST['last_name'] ?? ''),
            'spouse_name' => trim($_POST['spouse_name'] ?? ''),
            'children' => $children,
            'phone' => trim($_POST['phone'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'review' => trim($_POST['review'] ?? ''),
            'how_heard_about_us' => trim($_POST['how_heard_about_us'] ?? ''),
        ];
    }

    /** @param array<string, mixed> $data */
    private function validateSubmission(array $data): ?string
    {
        if (!$data['first_name'] || !$data['last_name']) {
            return 'Please fill in your name.';
        }

        if (!$data['phone']) {
            return 'Please provide a phone number so we can follow up.';
        }

        if (!$data['email']) {
            return 'Please enter your email address.';
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return 'Please enter a valid email address.';
        }

        return null;
    }

    /** @param array<string, mixed> $data */
    private function formViewData(array $data = [], ?string $error = null): array
    {
        return [
            'title' => 'First-time Visitor',
            'churchName' => SettingsService::churchName(),
            'slogan' => 'Transformed lives',
            'error' => $error,
            'data' => $data,
        ];
    }
}
