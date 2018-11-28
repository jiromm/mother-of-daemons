<?php

namespace Jiromm\MotherOfDaemons\Command;

use Jiromm\MotherOfDaemons\Daemon\DaemonInterface;
use Jiromm\MotherOfDaemons\Exception\InvalidArgumentException;
use Jiromm\MotherOfDaemons\Exception\NotFoundException;
use Jiromm\MotherOfDaemons\MotherOfDaemons;
use Jiromm\MotherOfDaemons\Router\RouterInterface;
use Psr\Http\Message\ServerRequestInterface;

class Dispatcher
{
    protected $motherofDaemons;
    protected $router;

    public function __construct(MotherOfDaemons $motherOfDaemons, RouterInterface $router)
    {
        $this->motherofDaemons = $motherOfDaemons;
        $this->router = $router;

        $this->listen();
    }

    public function listen()
    {
        $mod = $this->motherofDaemons;
        $router = $this->router;

        $router->on('/start', function (ServerRequestInterface $request, ...$params) use ($mod) {
            try {
                if (!isset($request->getQueryParams()['daemon'])) {
                    throw new InvalidArgumentException('Daemon not defined');
                }

                /**
                 * @var DaemonInterface $daemon
                 */
                $found = false;
                foreach ($mod->getDaemons() as $daemon) {
                    if ($daemon->getName() == $request->getQueryParams()['daemon']) {
                        $mod->createDaemon($daemon);
                        $found = true;
                        break;
                    }
                }

                if (!$found) {
                    throw new InvalidArgumentException('Daemon name is wrong');
                }

                $result = [
                    'status' => 'success',
                    'message' => sprintf('Daemon [%s] successfully started', $request->getQueryParams()['daemon']),
                ];
            } catch (\Throwable $e) {
                $result = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }

            return json_encode($result, JSON_PRETTY_PRINT);
        });

        $router->on('/stop', function (ServerRequestInterface $request, ...$params) use ($mod) {
            $pids = [];

            try {
                if (!isset($request->getQueryParams()['daemon'])) {
                    throw new InvalidArgumentException('Daemon not defined');
                }

                $daemonName = $request->getQueryParams()['daemon'];
                $selectedDaemon = null;

                foreach($mod->getDaemons() as $daemon) {
                    if ($daemon->getName() == $daemonName) {
                        $selectedDaemon = $daemon;
                        break;
                    }
                }

                if ($selectedDaemon) {
                    foreach ($mod->getProcessManager()->get($selectedDaemon->getCommand()) as $pid => $process) {
                        $pids[] = $pid;
                        $mod->removeDaemon(
                            $process
                        );
                    }

                    $pidsString = array_reduce($pids, function ($carry, $item) {
                        return $carry . "#{$item} ";
                    }, '');
                    $result = [
                        'status' => 'success',
                        'message' => sprintf(
                            'Daemon %s with the PIDs %ssuccessfully terminated',
                            $daemonName,
                            $pidsString
                        ),
                    ];
                } else {
                    $result = [
                        'status' => 'error',
                        'message' => sprintf('Daemon %s not found', $daemonName),
                    ];
                }
            } catch (\Throwable $e) {
                $result = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }

            return json_encode($result, JSON_PRETTY_PRINT);
        });

        $router->on('/list', function (ServerRequestInterface $request) use ($mod) {
            try {
                $processList = [];
                foreach ($mod->getProcessManager()->getList() as $command => $process) {
                    /**
                     * @var null|DaemonInterface $matchedDaemon
                     */
                    $matchedDaemon = null;

                    foreach ($mod->getDaemons() as $daemon) {
                        if ($daemon->getCommand() == $command) {
                            $matchedDaemon = $daemon;
                            break;
                        }
                    }

                    if (!$matchedDaemon instanceof DaemonInterface) {
                        throw new NotFoundException(sprintf('[%s] command specific daemon not found', $command));
                    }

                    $processList[] = [
                        'status' => count($process) ? 'Active' : 'Inactive',
                        'name' => $matchedDaemon->getName(),
                        'config' => $matchedDaemon->getConfig()->getArrayCopy(),
                        'process' => [
                            'command' => $command,
                            'pids' => array_keys($process),
                        ],
                    ];
                }

                $result = [
                    'status' => 'success',
                    'daemons' => $processList,
                ];
            } catch (\Throwable $e) {
                $result = [
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ];
            }

            return json_encode($result, JSON_PRETTY_PRINT);
        });

        $router->on('/', function (ServerRequestInterface $request) {
            return 'Welcome!';
        });
    }
}
