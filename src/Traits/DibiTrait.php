<?php

namespace Solcloud\Consumer\Traits;

use Dibi\Connection as Context;
use Dibi\DriverException;
use Exception;

trait DibiTrait
{

    /** @var Context */
    private $dibi;

    public function setDibi(Context $dibi): void
    {
        $this->dibi = $dibi;
    }

    public function getDibi(): Context
    {
        return $this->dibi;
    }

    public function dibiReconnect(): void
    {
        try {
            $this->getDibi()->query('SELECT 1')->fetch();
        } catch (DriverException $exIgnore) {
            $this->getDibi()->connect();
        }
    }

    public function dibiReconnectOrExitOnFail(int $sleepTimeSecondsBeforeRejectAndExit = 1): void
    {
        try {
            $this->dibiReconnect();
        } catch (Exception $ex) {
            $this->getLogger()->critical($ex);
            sleep($sleepTimeSecondsBeforeRejectAndExit);
            $this->sendReject();
            exit;
        }
    }

}
