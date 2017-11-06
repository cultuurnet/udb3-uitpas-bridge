<?php

namespace CultuurNet\UDB3\UiTPAS\EventConsumer\Label;

interface UiTPASLabelsRepository
{
    /**
     * @return string[]
     */
    public function loadAll();
}
