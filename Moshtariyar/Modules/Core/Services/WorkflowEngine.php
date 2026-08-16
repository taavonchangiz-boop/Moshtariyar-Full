<?php

namespace Modules\Core\Services;

use Modules\Automation\Services\WorkflowEngine as AutomationWorkflowEngine;

/**
 * رابط سازگاری موتور قدیمی با موتور مرکزی اتوماسیون.
 *
 * مسیرهای قدیمی پروژه هنوز ممکن است این کلاس را صدا بزنند؛
 * بنابراین آن را نگه می‌داریم اما اجرای واقعی فقط در یک موتور انجام می‌شود.
 */
class WorkflowEngine
{
    public function __construct(
        protected AutomationWorkflowEngine $automationEngine
    ) {
    }

    public function fireEvent(string $eventName, $subject = null, array $extraData = []): void
    {
        $this->automationEngine->fireEvent($eventName, $subject, $extraData);
    }
}