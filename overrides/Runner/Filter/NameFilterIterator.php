<?php

/*
 * BSD 3-Clause License
 *
 * Copyright (c) 2001-2023, Sebastian Bergmann
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice, this
 *    list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * 3. Neither the name of the copyright holder nor the names of its
 *    contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

declare(strict_types=1);

/*
 * This file is part of PHPUnit.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPUnit\Runner\Filter;

use PHPUnit\Framework\Test;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use RecursiveFilterIterator;
use RecursiveIterator;

use function end;
use function preg_match;

/**
 * @internal This class is not covered by the backward compatibility promise for PHPUnit
 */
abstract class NameFilterIterator extends RecursiveFilterIterator
{
    /**
     * @psalm-var non-empty-string
     */
    private readonly string $regularExpression;

    /**
     * @psalm-var ?int
     */
    private readonly ?int $dataSetMinimum;

    /**
     * @psalm-var ?int
     */
    private readonly ?int $dataSetMaximum;

    /**
     * @psalm-param RecursiveIterator<int, Test> $iterator
     * @psalm-param non-empty-string $filter
     */
    public function __construct(RecursiveIterator $iterator, string $filter)
    {
        parent::__construct($iterator);

        $preparedFilter = $this->prepareFilter($filter);

        $this->regularExpression = $preparedFilter['regularExpression'];
        $this->dataSetMinimum = $preparedFilter['dataSetMinimum'];
        $this->dataSetMaximum = $preparedFilter['dataSetMaximum'];
    }

    public function accept(): bool
    {
        $test = $this->getInnerIterator()->current();

        if ($test instanceof TestSuite) {
            return true;
        }

        if (! $test instanceof TestCase) {
            return false;
        }

        $name = $test::class.'::'.$test->nameWithDataSet();

        $accepted = @preg_match($this->regularExpression, $name, $matches) === 1;

        if ($accepted && isset($this->dataSetMaximum)) {
            $set = end($matches);
            $accepted = $set >= $this->dataSetMinimum && $set <= $this->dataSetMaximum;
        }

        return $accepted;
    }

    /**
     * @psalm-param non-empty-string $filter
     *
     * @psalm-return array{regularExpression: non-empty-string, dataSetMinimum: ?int, dataSetMaximum: ?int}
     */
    abstract protected function prepareFilter(string $filter): array;
}
