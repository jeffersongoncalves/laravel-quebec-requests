<?php

namespace JeffersonGoncalves\QuebecRequests\Enums;

/**
 * Rights of the person concerned under Quebec's private sector privacy act (Law 25).
 */
enum RequestType: string
{
    case Access = 'access';                          // s. 27
    case Portability = 'portability';                // s. 27: a structured, commonly used technological format
    case Rectification = 'rectification';            // s. 28: inaccurate, incomplete or equivocal information
    case Deletion = 'deletion';                      // s. 28: outdated, unjustified or illegally collected information
    case Deindexation = 'deindexation';              // s. 28.1: cease dissemination, de-index a hyperlink
    case AutomatedDecision = 'automated_decision';   // s. 12.1: the information, reasons and factors behind an automated decision

    /**
     * Types fulfil() handles on its own; the others are answered by hand, then complete().
     */
    public function isAutomatic(): bool
    {
        return in_array($this, [self::Access, self::Portability, self::Deletion], true);
    }
}
