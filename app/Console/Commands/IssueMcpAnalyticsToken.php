<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class IssueMcpAnalyticsToken extends Command
{
    protected $signature = 'artera:mcp-issue-token
                            {email : Authorized Artera admin email}
                            {--expires-days=30 : Token lifetime in days (1-90)}
                            {--replace : Revoke the existing MCP token for this admin first}';

    protected $description = 'Issue a least-privilege Sanctum token for the read-only Artera MCP analytics API.';

    public function handle(): int
    {
        $email = mb_strtolower(trim((string) $this->argument('email')));
        $allowedEmails = config('mcp_analytics.allowed_admin_emails', []);
        if (!in_array($email, $allowedEmails, true)) {
            $this->error('The email is not present in MCP_ANALYTICS_ADMIN_EMAILS.');

            return self::FAILURE;
        }

        $days = (int) $this->option('expires-days');
        if ($days < 1 || $days > 90) {
            $this->error('--expires-days must be between 1 and 90.');

            return self::FAILURE;
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error('No Artera user was found for this email.');

            return self::FAILURE;
        }

        $tokenName = config('mcp_analytics.token_name', 'mcp-analytics');
        $existingTokens = $user->tokens()->where('name', $tokenName);
        if ($existingTokens->exists() && !$this->option('replace')) {
            $this->error('An MCP analytics token already exists. Re-run with --replace after updating the MCP server secret.');

            return self::FAILURE;
        }

        if ($this->option('replace')) {
            $existingTokens->delete();
        }

        $newToken = $user->createToken($tokenName, ['mcp:analytics']);
        $newToken->accessToken->forceFill([
            'expires_at' => now()->addDays($days),
        ])->save();

        $this->warn('Copy this token now. It is not stored in plaintext and will not be shown again.');
        $this->line($newToken->plainTextToken);
        $this->newLine();
        $this->info("Expires: {$newToken->accessToken->expires_at->toIso8601String()}");

        return self::SUCCESS;
    }
}
