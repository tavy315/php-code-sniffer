<?php
namespace I2ct\Sniffs\Formatting;

use PHP_CodeSniffer_File;
use PHP_CodeSniffer_Sniff;

/**
 * Customized Generic.Formatting.DisallowMultipleStatements rule.
 * - Fixed adding 2 blank lines when applying this fixer with
 *   Squiz.Functions.MultiLineFunctionDeclaration.ContentAfterBrace fixer together.
 */
class DisallowMultipleStatementsSniff implements PHP_CodeSniffer_Sniff
{
    /**
     * {@inheritdoc}
     */
    public function register()
    {
        return [ T_SEMICOLON ];
    }

    /**
     * Called when one of the token types that this sniff is listening for
     * is found.
     *
     * The stackPtr variable indicates where in the stack the token was found.
     * A sniff can acquire information this token, along with all the other
     * tokens within the stack by first acquiring the token stack:
     *
     * <code>
     *    $tokens = $phpcsFile->getTokens();
     *    echo 'Encountered a '.$tokens[$stackPtr]['type'].' token';
     *    echo 'token information: ';
     *    print_r($tokens[$stackPtr]);
     * </code>
     *
     * If the sniff discovers an anomaly in the code, they can raise an error
     * by calling addError() on the PHP_CodeSniffer_File object, specifying an error
     * message and the position of the offending token:
     *
     * <code>
     *    $phpcsFile->addError('Encountered an error', $stackPtr);
     * </code>
     *
     * @param \PHP_CodeSniffer_File $phpcsFile The PHP_CodeSniffer file where the
     *                                         token was found.
     * @param int                   $stackPtr  The position in the PHP_CodeSniffer
     *                                         file's token stack where the token
     *                                         was found.
     *
     * @return void|int Optionally returns a stack pointer. The sniff will not be
     *                  called again on the current file until the returned stack
     *                  pointer is reached. Return (count($tokens) + 1) to skip
     *                  the rest of the file.
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        $prev = $phpcsFile->findPrevious([ T_SEMICOLON, T_OPEN_TAG ], ($stackPtr - 1));
        if ($prev === false || $tokens[$prev]['code'] === T_OPEN_TAG) {
            $phpcsFile->recordMetric($stackPtr, 'Multiple statements on same line', 'no');

            return;
        }

        // Ignore multiple statements in a FOR condition.
        if (isset($tokens[$stackPtr]['nested_parenthesis']) === true) {
            foreach ($tokens[$stackPtr]['nested_parenthesis'] as $bracket) {
                if (isset($tokens[$bracket]['parenthesis_owner']) === false) {
                    // Probably a closure sitting inside a function call.
                    continue;
                }

                $owner = $tokens[$bracket]['parenthesis_owner'];
                if ($tokens[$owner]['code'] === T_FOR) {
                    return;
                }
            }
        }

        /*
         * Fixed adding 2 blank lines when applying this fixer with
         * Squiz.Functions.MultiLineFunctionDeclaration.ContentAfterBrace fixer together.
         */
        if ($tokens[$prev]['line'] === $tokens[$stackPtr]['line']
            && $tokens[$prev + 1]['code'] != T_CLOSE_CURLY_BRACKET
        ) {
            $phpcsFile->recordMetric($stackPtr, 'Multiple statements on same line', 'yes');

            $error = 'Each PHP statement must be on a line by itself';
            $fix = $phpcsFile->addFixableError($error, $stackPtr, 'SameLine');
            if ($fix === true) {
                $phpcsFile->fixer->beginChangeset();
                $phpcsFile->fixer->addNewline($prev);
                if ($tokens[($prev + 1)]['code'] === T_WHITESPACE) {
                    $phpcsFile->fixer->replaceToken(($prev + 1), '');
                }

                $phpcsFile->fixer->endChangeset();
            }
        } else {
            $phpcsFile->recordMetric($stackPtr, 'Multiple statements on same line', 'no');
        }
    }
}
