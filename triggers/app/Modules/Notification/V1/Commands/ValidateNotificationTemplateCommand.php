<?php

namespace App\Modules\Notification\V1\Commands;

use App\Modules\Notification\V1\Models\NotificationTemplate;
use App\Modules\Notification\V1\Services\NotificationTemplate\NotificationTemplateValidator;
use Illuminate\Console\Command;

class ValidateNotificationTemplateCommand extends Command
{
    protected $signature = 'app:validate-notification-templates';

    protected $description = 'Validate notification templates';

    public function handle(): void
    {
        $validator = app(NotificationTemplateValidator::class);

        NotificationTemplate::query()
            ->active()
            ->each(function (NotificationTemplate $template) use ($validator) {
                $this->info("Validating template ID:{$template->id}");
                // try {
                    $validator->validate($template);
                // } catch (\Exception $e) {
                //     $this->error($e->getMessage());
                // }
            });

        $this->info('Done');
    }
}
