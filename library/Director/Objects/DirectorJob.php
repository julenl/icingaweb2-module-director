<?php

namespace Icinga\Module\Director\Objects;

use Icinga\Module\Director\Data\Db\DbObjectWithSettings;
use Icinga\Module\Director\Hook\JobHook;
use Exception;
use InvalidArgumentException;

class DirectorJob extends DbObjectWithSettings
{
    /** @var JobHook */
    protected $job;

    protected $table = 'director_job';

    protected $keyName = 'id';

    protected $autoincKeyName = 'id';

    protected $defaultProperties = [
        'id'                     => null,
        'job_name'               => null,
        'job_class'              => null,
        'disabled'               => null,
        'run_interval'           => null,
        'last_attempt_succeeded' => null,
        'ts_last_attempt'        => null,
        'ts_last_error'          => null,
        'last_error_message'     => null,
        'timeperiod_id'          => null,
    ];

    protected $stateProperties = [
        'last_attempt_succeeded',
        'last_error_message',
        'ts_last_attempt',
        'ts_last_error',
    ];

    protected $settingsTable = 'director_job_setting';

    protected $settingsRemoteId = 'job_id';

    /**
     * @return JobHook
     */
    public function job()
    {
        if ($this->job === null) {
            $class = $this->get('job_class');
            $this->job = new $class;
            $this->job->setDb($this->connection);
            $this->job->setDefinition($this);
        }

        return $this->job;
    }

    /**
     * @throws \Icinga\Module\Director\Exception\DuplicateKeyException
     */
    public function run()
    {
        $job = $this->job();
        $this->set('ts_last_attempt', date('Y-m-d H:i:s'));

        try {
            $job->run();
            $this->set('last_attempt_succeeded', 'y');
        } catch (Exception $e) {
            $this->set('ts_last_error', date('Y-m-d H:i:s'));
            $this->set('last_error_message', $e->getMessage());
            $this->set('last_attempt_succeeded', 'n');
        }

        if ($this->hasBeenModified()) {
            $this->store();
        }
    }

    /**
     * @return bool
     * @throws \Icinga\Exception\NotFoundError
     */
    public function shouldRun()
    {
        return (! $this->hasBeenDisabled()) && $this->isPending();
    }

    /**
     * @return bool
     * @throws \Icinga\Exception\NotFoundError
     */
    public function isOverdue()
    {
        if (! $this->shouldRun()) {
            return false;
        }

        return (
            strtotime($this->get('ts_last_attempt')) + $this->get('run_interval') * 2
        ) < time();
    }

    public function hasBeenDisabled()
    {
        return $this->get('disabled') === 'y';
    }

    /**
     * @return bool
     * @throws \Icinga\Exception\NotFoundError
     */
    public function isPending()
    {
        if ($this->get('ts_last_attempt') === null) {
            return $this->isWithinTimeperiod();
        }

        if (strtotime($this->get('ts_last_attempt')) + $this->get('run_interval') < time()) {
            return $this->isWithinTimeperiod();
        }

        return false;
    }

    /**
     * @return bool
     * @throws \Icinga\Exception\NotFoundError
     */
    public function isWithinTimeperiod()
    {
        if ($this->hasTimeperiod()) {
            return $this->timeperiod()->isActive();
        } else {
            return true;
        }
    }

    public function lastAttemptSucceeded()
    {
        return $this->get('last_attempt_succeeded') === 'y';
    }

    public function hasTimeperiod()
    {
        return $this->get('timeperiod_id') !== null;
    }

    /**
     * @param $timeperiod
     * @return $this
     * @throws \Icinga\Exception\NotFoundError
     */
    public function setTimeperiod($timeperiod)
    {
        if (is_string($timeperiod)) {
            $timeperiod = IcingaTimePeriod::load($timeperiod, $this->connection);
        } elseif (! $timeperiod instanceof IcingaTimePeriod) {
            throw new InvalidArgumentException('TimePeriod expected');
        }

        $this->set('timeperiod_id', $timeperiod->get('id'));

        return $this;
    }

    /**
     * @return object
     * @throws \Icinga\Exception\NotFoundError
     */
    public function export()
    {
        $plain = (object) $this->getProperties();
        $plain->originalId = $plain->id;
        unset($plain->id);
        unset($plain->timeperiod_id);
        if ($this->hasTimeperiod()) {
            $plain->timeperiod = $this->timeperiod()->getObjectName();
        }

        foreach ($this->stateProperties as $key) {
            unset($plain->$key);
        }
        $plain->settings = $this->job()->exportSettings();

        return $plain;
    }

    /**
     * @return IcingaTimePeriod
     * @throws \Icinga\Exception\NotFoundError
     */
    protected function timeperiod()
    {
        return IcingaTimePeriod::loadWithAutoIncId($this->get('timeperiod_id'), $this->connection);
    }
}
