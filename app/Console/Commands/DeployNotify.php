<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeployNotify extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'deploy:notify {--message= : Custom deployment message}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send deployment notification to configured channels';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Sending deployment notifications...');

        $message = $this->option('message') ?? 'Application deployed successfully';
        $environment = config('app.env');
        $version = $this->getVersion();
        $deployer = $this->getDeployer();
        $timestamp = now()->toDateTimeString();

        $notification = [
            'message' => $message,
            'environment' => $environment,
            'version' => $version,
            'deployed_by' => $deployer,
            'timestamp' => $timestamp,
            'url' => config('app.url'),
        ];

        $sent = false;

        // Send to Slack
        if (config('monitoring.alerting.channels.slack.enabled')) {
            $this->sendSlackNotification($notification);
            $sent = true;
        }

        // Send to email
        if (config('monitoring.alerting.channels.email.enabled')) {
            $this->sendEmailNotification($notification);
            $sent = true;
        }

        // Log deployment
        Log::info('Deployment completed', $notification);

        if ($sent) {
            $this->info('Deployment notifications sent successfully!');
            return Command::SUCCESS;
        }

        $this->warn('No notification channels configured');
        return Command::SUCCESS;
    }

    /**
     * Send Slack notification.
     */
    protected function sendSlackNotification(array $notification): void
    {
        try {
            $webhookUrl = config('monitoring.alerting.channels.slack.webhook_url');
            
            if (!$webhookUrl) {
                return;
            }

            $color = $notification['environment'] === 'production' ? 'good' : 'warning';

            Http::post($webhookUrl, [
                'username' => 'Deployment Bot',
                'icon_emoji' => ':rocket:',
                'attachments' => [
                    [
                        'color' => $color,
                        'title' => '🚀 Deployment Notification',
                        'text' => $notification['message'],
                        'fields' => [
                            [
                                'title' => 'Environment',
                                'value' => $notification['environment'],
                                'short' => true,
                            ],
                            [
                                'title' => 'Version',
                                'value' => $notification['version'],
                                'short' => true,
                            ],
                            [
                                'title' => 'Deployed By',
                                'value' => $notification['deployed_by'],
                                'short' => true,
                            ],
                            [
                                'title' => 'Time',
                                'value' => $notification['timestamp'],
                                'short' => true,
                            ],
                            [
                                'title' => 'URL',
                                'value' => $notification['url'],
                                'short' => false,
                            ],
                        ],
                        'footer' => config('app.name'),
                        'ts' => now()->timestamp,
                    ],
                ],
            ]);

            $this->info('✓ Slack notification sent');
        } catch (\Exception $e) {
            $this->error('✗ Failed to send Slack notification: ' . $e->getMessage());
            Log::error('Failed to send Slack deployment notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send email notification.
     */
    protected function sendEmailNotification(array $notification): void
    {
        try {
            $recipients = config('monitoring.alerting.channels.email.recipients', []);
            
            if (empty($recipients)) {
                return;
            }

            $subject = "[{$notification['environment']}] Deployment Notification";
            
            $body = "
Deployment Notification
=======================

Message: {$notification['message']}
Environment: {$notification['environment']}
Version: {$notification['version']}
Deployed By: {$notification['deployed_by']}
Time: {$notification['timestamp']}
URL: {$notification['url']}

---
{$notification['environment']} environment
            ";

            foreach ($recipients as $recipient) {
                \Mail::raw($body, function ($message) use ($recipient, $subject) {
                    $message->to($recipient)
                        ->subject($subject);
                });
            }

            $this->info('✓ Email notifications sent');
        } catch (\Exception $e) {
            $this->error('✗ Failed to send email notification: ' . $e->getMessage());
            Log::error('Failed to send email deployment notification', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Get application version.
     */
    protected function getVersion(): string
    {
        // Try to get version from git
        try {
            $version = trim(shell_exec('git describe --tags --always 2>/dev/null'));
            if ($version) {
                return $version;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Fallback to config
        return config('app.version', '1.0.0');
    }

    /**
     * Get deployer information.
     */
    protected function getDeployer(): string
    {
        // Try to get from git
        try {
            $deployer = trim(shell_exec('git config user.name 2>/dev/null'));
            if ($deployer) {
                return $deployer;
            }
        } catch (\Exception $e) {
            // Ignore
        }

        // Try to get from environment
        $user = getenv('USER') ?: getenv('USERNAME');
        if ($user) {
            return $user;
        }

        return 'Unknown';
    }
}
