<?php
namespace I2ct\CodeSniffer\Helpers;

use PHP_CodeSniffer_File;

/**
 * Class Helper
 * Provides information for a class.
 */
class ClassHelper
{
    /**
     * CodeSniffer file reference
     *
     * @var \PHP_CodeSniffer_File
     */
    protected $phpcsFile;

    /**
     * Cache for class parents and interfaces.
     *
     * @var array
     */
    protected $parentsAndInterfaces;

    /**
     * Cache for test classes indexed by stack pointer.
     *
     * @var array
     */
    protected $testClasses = [];

    /**
     * Cache for test class methods indexed by stack pointer.
     *
     * @var array
     */
    protected $testClassMethods = [];

    /**
     * PHPUnit test class prototypes
     *
     * @var array
     */
    protected static $testClassPrototypes = [
        'PHPUnit_Framework_TestCase',
        'PHPUnit\\Framework\\TestCase',
    ];

    /**
     * Constructor
     *
     * @param \PHP_CodeSniffer_File $phpcsFile
     */
    public function __construct(PHP_CodeSniffer_File $phpcsFile)
    {
        $this->phpcsFile = $phpcsFile;
    }

    /**
     * Get class parents and interfaces.
     * Returns array of class and interface names or false if the class cannot be loaded.
     *
     * @param  int    $stackPtr
     * @param  string $reason
     *
     * @return array|bool
     */
    public function getClassParentsAndInterfaces($stackPtr, $reason)
    {
        if ($this->parentsAndInterfaces === null) {
            $phpcsFile = $this->phpcsFile;
            $tokens = $phpcsFile->getTokens();
            $nsStart = $phpcsFile->findNext([ T_NAMESPACE ], 0);
            $class = '';

            // Set the default return value.
            $this->parentsAndInterfaces = false;

            // Build the namespace.
            if ($nsStart !== false) {
                $nsEnd = $phpcsFile->findNext([ T_SEMICOLON ], $nsStart + 2);
                for ($i = $nsStart + 2; $i < $nsEnd; $i++) {
                    $class .= $tokens[$i]['content'];
                }
                $class .= '\\';
            } else {
                $nsEnd = 0;
            }

            // Find the class/interface declaration.
            $classPtr = $phpcsFile->findNext([ T_CLASS, T_INTERFACE ], $nsEnd);
            if ($classPtr !== false) {
                $class .= $phpcsFile->getDeclarationName($classPtr);
                if (class_exists($class) || interface_exists($class)) {
                    $this->parentsAndInterfaces = array_merge(class_parents($class), class_implements($class));
                } else {
                    $warning = 'Need class loader to ' . $reason;
                    $phpcsFile->addWarning($warning, $stackPtr, 'Internal.I2ct.NeedClassLoader');
                }
            }
        }

        return $this->parentsAndInterfaces;
    }

    /**
     * Check if a class is a PHPUnit test class.
     *
     * @param  int $stackPtr
     *
     * @return bool
     */
    public function isTestClass($stackPtr)
    {
        if (!array_key_exists($stackPtr, $this->testClasses)) {
            $this->testClasses[$stackPtr] = false;

            $classes = $this->getClassParentsAndInterfaces($stackPtr, 'check for PHPUnit test class');
            if ($classes !== false) {
                foreach ($classes as $class) {
                    if (in_array($class, self::$testClassPrototypes)) {
                        $this->testClasses[$stackPtr] = true;
                        break;
                    }
                }
            }
        }

        return $this->testClasses[$stackPtr];
    }

    /**
     * Check if a method is a PHPUnit test class method.
     *
     * @param  int $stackPtr
     *
     * @return bool
     */
    public function isTestClassMethod($stackPtr)
    {
        if (!array_key_exists($stackPtr, $this->testClassMethods)) {
            $this->testClassMethods[$stackPtr] = false;
            if ($this->isTestClass($stackPtr)) {
                $props = $this->phpcsFile->getMethodProperties($stackPtr);
                if ($props['scope'] === 'public' &&
                    !$props['is_abstract'] &&
                    !$props['is_closure'] &&
                    stripos($this->phpcsFile->getDeclarationName($stackPtr), 'test') === 0
                ) {
                    $this->testClassMethods[$stackPtr] = true;
                }
            }
        }

        return $this->testClassMethods[$stackPtr];
    }
}
