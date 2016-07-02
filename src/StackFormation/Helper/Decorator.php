<?php

namespace StackFormation\Helper;

class Decorator
{


    public static function decorateStatus($status)
    {
        // it's so easy to miss this one...
        if ($status == 'UPDATE_ROLLBACK_COMPLETE') {
            return "<fg=green>UPDATE</><fg=red>_ROLLBACK_</><fg=green>COMPLETE</>";
        }
        if (strpos($status, 'IN_PROGRESS') !== false) {
            return "<fg=yellow>$status</>";
        }
        if (strpos($status, 'COMPLETE') !== false) {
            return "<fg=green>$status</>";
        }
        if (strpos($status, 'FAILED') !== false) {
            return "<fg=red>$status</>";
        }
        return $status;
    }

    public static function decorateChangesetReplacement($changeSetReplacement)
    {
        if ($changeSetReplacement == 'Conditional') {
            return "<fg=yellow>$changeSetReplacement</>";
        }
        if ($changeSetReplacement == 'False') {
            return "<fg=green>$changeSetReplacement</>";
        }
        if ($changeSetReplacement == 'True') {
            return "<fg=red>$changeSetReplacement</>";
        }
        return $changeSetReplacement;
    }
}
