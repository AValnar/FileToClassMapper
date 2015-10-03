<?php
/**
 * Copyright (C) 2015  Alexander Schmidt
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * @author     Alexander Schmidt <mail@story75.com>
 * @copyright  Copyright (c) 2015, Alexander Schmidt
 * @date       03.10.2015
 */

namespace AValnar\FileToClassMapper;


use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

final class Mapper
{
    /**
     * @var Finder
     */
    private $finder;

    /**
     * @var array
     */
    private $inPathPatterns;

    /**
     * @var array
     */
    private $excludePathPatterns;

    /**
     * @var array
     */
    private $names;

    /**
     * @param Finder $finder
     */
    public function __construct(Finder $finder)
    {
        $this->finder = $finder;
        $this->configure([], ['/tests/i'], '*.php');
    }

    /**
     * @param array $inPathPatterns
     * @param array $excludePathPatterns
     * @param array $names
     */
    public function configure($inPathPatterns = array(), $excludePathPatterns = array(), $names = array())
    {
        $this->inPathPatterns = $inPathPatterns;
        $this->excludePathPatterns = $excludePathPatterns;
        $this->names = $names;
    }

    /**
     * Create a map for a given path
     *
     * @param array $paths
     * @return array
     */
    public function createMap(...$paths)
    {
        $classes = [];

        $this->finder->files()
            ->ignoreUnreadableDirs()
            ->in($paths);

        foreach($this->excludePathPatterns as $exclude)
        {
            $this->finder->notPath($exclude);
        }

        foreach($this->inPathPatterns as $inPath)
        {
            $this->finder->path($inPath);
        }

        foreach($this->names as $name)
        {
            $this->finder->name($name);
        }

        /** @var SplFileInfo $file */
        foreach ($this->finder as $file) {
            $content = file_get_contents($file->getPathname());

            preg_match('^\s*class ([\S]*)\s*(extends|implements|{)^', $content, $match, PREG_OFFSET_CAPTURE);

            if (isset($match[1])) {
                $className = '\\'. trim($match[1][0]);
                $offset = $match[1][1];
            } else {
                continue;
            }

            preg_match('|\s*namespace\s*([\S]*)\s*;|', substr($content, 0 , $offset), $match);

            if (isset($match[1]) && trim($match[1]) !== '') {
                $className = '\\' . trim($match[1]) . $className;
            }

            if ($className !== '\\') {
                $classes[$file->getPathname()] = $className;
            }
        }

        return $classes;
    }
}