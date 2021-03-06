<?php
/**
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */

/**
 * Uses glob patterns to perform filename transformations.
 *
 * @author  Andreas Aderhold, andi@binarycloud.com
 * @package phing.mappers
 */
class GlobMapper implements FileNameMapper
{
    /**
     * Part of &quot;from&quot; pattern before the <code>.*</code>.
     *
     * @var string $fromPrefix
     */
    private $fromPrefix = null;

    /**
     * Part of &quot;from&quot; pattern after the <code>.*</code>.
     *
     * @var string $fromPostfix
     */
    private $fromPostfix = null;

    /**
     * Length of the prefix (&quot;from&quot; pattern).
     *
     * @var int $prefixLength
     */
    private $prefixLength;

    /**
     * Length of the postfix (&quot;from&quot; pattern).
     *
     * @var int $postfixLength
     */
    private $postfixLength;

    /**
     * Part of &quot;to&quot; pattern before the <code>*.</code>.
     *
     * @var string $toPrefix
     */
    private $toPrefix = null;

    /**
     * Part of &quot;to&quot; pattern after the <code>*.</code>.
     *
     * @var string $toPostfix
     */
    private $toPostfix = null;

    private $fromContainsStar = false;
    private $toContainsStar = false;
    private $handleDirSep = false;
    private $caseSensitive = true;

    /**
     * Attribute specifying whether to ignore the difference
     * between / and \ (the two common directory characters).
     *
     * @param boolean $handleDirSep a boolean, default is false.
     */
    public function setHandleDirSep($handleDirSep)
    {
        $this->handleDirSep = $handleDirSep;
    }

    /**
     * Attribute specifying whether to ignore the difference
     * between / and \ (the two common directory characters).
     */
    public function getHandleDirSep()
    {
        return $this->handleDirSep;
    }

    /**
     * Attribute specifying whether to ignore the case difference
     * in the names.
     *
     * @param boolean $caseSensitive a boolean, default is false.
     */
    public function setCaseSensitive($caseSensitive)
    {
        $this->caseSensitive = $caseSensitive;
    }

    /**
     * {@inheritdoc}
     *
     * @param  mixed $sourceFileName
     * @return array|null
     */
    public function main($sourceFileName)
    {
        $modName = $this->modifyName($sourceFileName);
        if ($this->fromPrefix === null
            || (strlen($sourceFileName) < ($this->prefixLength + $this->postfixLength)
                || (!$this->fromContainsStar && !$modName === $this->modifyName($this->fromPrefix)))
            || ($this->fromContainsStar
                && (!StringHelper::startsWith($this->modifyName($this->fromPrefix), $modName)
                    || !StringHelper::endsWith($this->modifyName($this->fromPostfix), $modName)))
        ) {
            return null;
        }
        return [
            $this->toPrefix . (
            $this->toContainsStar
                ? $this->extractVariablePart($sourceFileName) . $this->toPostfix
                : ''
            )
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param  string $from
     * @return void
     */
    public function setFrom($from)
    {
        if ($from === null) {
            throw new BuildException("this mapper requires a 'from' attribute");
        }

        $index = strrpos($from, '*');

        if ($index === false) {
            $this->fromPrefix = $from;
            $this->fromPostfix = "";
        } else {
            $this->fromPrefix = substr($from, 0, $index);
            $this->fromPostfix = substr($from, $index + 1);
            $this->fromContainsStar = true;
        }
        $this->prefixLength = strlen($this->fromPrefix);
        $this->postfixLength = strlen($this->fromPostfix);
    }

    /**
     * Sets the &quot;to&quot; pattern. Required.
     * {@inheritdoc}
     *
     * @param  string $to
     * @return void
     */
    public function setTo($to)
    {
        if ($to === null) {
            throw new BuildException("this mapper requires a 'to' attribute");
        }

        $index = strrpos($to, '*');
        if ($index === false) {
            $this->toPrefix = $to;
            $this->toPostfix = "";
        } else {
            $this->toPrefix = substr($to, 0, $index);
            $this->toPostfix = substr($to, $index + 1);
            $this->toContainsStar = true;
        }
    }

    /**
     * Extracts the variable part.
     *
     * @param  string $name
     * @return string
     */
    private function extractVariablePart($name)
    {
        return StringHelper::substring($name, $this->prefixLength, strlen($name) - $this->postfixLength - 1);
    }

    /**
     * modify string based on dir char mapping and case sensitivity
     *
     * @param  string $name the name to convert
     * @return string the converted name
     */
    private function modifyName($name)
    {
        if (!$this->caseSensitive) {
            $name = strtolower($name);
        }
        if ($this->handleDirSep) {
            if (strpos('\\', $name) !== false) {
                $name = str_replace('\\', '/', $name);
            }
        }

        return $name;
    }
}
