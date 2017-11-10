<?php

namespace CultuurNet\UDB3\UiTPAS\EventConsumer\Label;

use CultuurNet\UDB3\Label;

interface UiTPASLabelsRepositoryInterface
{
    /**
     * @return Label[]
     */
    public function loadAll();
}
