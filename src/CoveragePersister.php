<?php
namespace AronSzigetvari\TestSelector;

use AronSzigetvari\TestSelector\Model\SourceFile;
use AronSzigetvari\TestSelector\Model\State;
use AronSzigetvari\TestSelector\Model\Test;

interface CoveragePersister
{
    public function findSourceFileByPath(string $path): SourceFile;

    public function findStateByCommit(string $path): State;

    public function findTestByName(string $name): Test;

    public function resetState(State $state, string $strategy): void;

    public function saveDependentRanges(array $ranges): void;
}