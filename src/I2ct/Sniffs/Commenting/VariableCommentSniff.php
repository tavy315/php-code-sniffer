<?php
namespace I2ct\Sniffs\Commenting;

use PHP_CodeSniffer;
use PHP_CodeSniffer_File;
use Squiz_Sniffs_Commenting_VariableCommentSniff;

/**
 * Customized some rules from Squiz.Commenting.VariableComment.
 * - Added 'bool' and 'int' into allowed variable types.
 */
class VariableCommentSniff extends Squiz_Sniffs_Commenting_VariableCommentSniff
{
    public function processMemberVar(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        PHP_CodeSniffer::$allowedTypes = array_unique(
            array_merge(
                PHP_CodeSniffer::$allowedTypes,
                [
                    'int',
                    'bool',
                ]
            )
        );
        parent::processMemberVar($phpcsFile, $stackPtr);
    }
}
