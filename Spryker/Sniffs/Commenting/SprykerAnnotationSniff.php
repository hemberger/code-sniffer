<?php

namespace Spryker\Sniffs\Commenting;

use PHP_CodeSniffer\Files\File;
use Spryker\Sniffs\AbstractSniffs\AbstractSprykerSniff;

/**
 * Checks if Spryker method annotations contain interface where needed.
 */
class SprykerAnnotationSniff extends AbstractSprykerSniff
{
    /**
     * @return array
     */
    public function register()
    {
        return [
            T_CLASS,
            T_INTERFACE,
        ];
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    public function process(File $phpCsFile, $stackPointer)
    {
        if (!$this->isCore($phpCsFile)) {
            return;
        }

        $this->checkAnnotations($phpCsFile, $stackPointer);
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $stackPointer
     *
     * @return void
     */
    protected function checkAnnotations(File $phpCsFile, $stackPointer)
    {
        $tokens = $phpCsFile->getTokens();

        $docBlockEndIndex = $this->findRelatedDocBlock($phpCsFile, $stackPointer);
        if (!$docBlockEndIndex) {
            return;
        }
        $docBlockStartIndex = $tokens[$docBlockEndIndex]['comment_opener'];
        if (!$docBlockStartIndex) {
            return;
        }

        $methodAnnotations = $this->getFixableMethodAnnotations($phpCsFile, $docBlockStartIndex, $docBlockEndIndex);
        if (!$methodAnnotations) {
            return;
        }

        foreach ($methodAnnotations as $methodAnnotation) {
            $fixable = $phpCsFile->addFixableError(
                sprintf('Interface must be used for %s annotation of %s', $methodAnnotation['method'], $methodAnnotation['class']),
                $methodAnnotation['index'],
                'Annotation.Interface.Invalid'
            );
            if (!$fixable) {
                continue;
            }

            $content = $methodAnnotation['interface'] . ' ' .$methodAnnotation['method'];

            $phpCsFile->fixer->beginChangeset();
            $phpCsFile->fixer->replaceToken($methodAnnotation['index'] + 2, $content);
            $phpCsFile->fixer->endChangeset();
        }
    }

    /**
     * @param \PHP_CodeSniffer\Files\File $phpCsFile
     * @param int $docBlockStartIndex
     * @param int $docBlockEndIndex
     * @return array
     */
    protected function getFixableMethodAnnotations(File $phpCsFile, $docBlockStartIndex, $docBlockEndIndex)
    {
        $tokens = $phpCsFile->getTokens();

        $annotations = [];
        for ($i = $docBlockStartIndex + 1; $i < $docBlockEndIndex; $i++) {
            if ($tokens[$i]['code'] !== T_DOC_COMMENT_TAG || $tokens[$i]['content'] !== '@method') {
                continue;
            }

            // T_DOC_COMMENT_STRING invalid, another sniff should check on this
            if (strpos($tokens[$i + 2]['content'], ' ') === false) {
                continue;
            }

            list ($class, $method) = explode(' ', $tokens[$i + 2]['content'], 2);

            $ignoredMethods = [
                'getFactory()',
                'getConfig()',
            ];
            if (in_array($method, $ignoredMethods, true)) {
                continue;
            }

            if (substr($class, -9) === 'Interface') {
                continue;
            }

            $interface = $class . 'Interface';
            $interfaceFile = str_replace($class, $interface, $phpCsFile->getFilename());
            if (!file_exists($interfaceFile)) {
                $phpCsFile->addError(
                    sprintf('Interface missing for %s annotation of %s', $method, $class),
                    $i,
                    'Annotation.Interface.Missing'
                );
                continue;
            }

            $annotation = [
                'index' => $i,
                'method' => $method,
                'class' => $class,
                'interface' => $interface,
            ];

            $annotations[] = $annotation;
        }

        return $annotations;
    }
}
