<?php

namespace AutoMapper\Tests\Fixtures\ComposeModels;

class Service
{
    public function __construct(
        public Development|null $develop = NULL,
        public Deployment|null $deploy = NULL,
        public string|float|bool|null|array $annotations = NULL,
        public bool|null $attach = NULL,
        public string|null|ServiceBuild $build = NULL,
        public ServiceBlkioConfig|null $blkioConfig = NULL,
        /** @var string|null[]|null */
        public array|null $capAdd = NULL,
        /** @var string|null[]|null */
        public array|null $capDrop = NULL,
        public ServiceCgroupEnum|null $cgroup = NULL,
        public string|null $cgroupParent = NULL,
        public null|string|array $command = NULL,
        /** @var string|null|ServiceConfigOrSecretItem[]|null */
        public array|null $configs = NULL,
        public string|null $containerName = NULL,
        public int|null $cpuCount = NULL,
        public int|null $cpuPercent = NULL,
        public float|string|null $cpuShares = NULL,
        public float|string|null $cpuQuota = NULL,
        public float|string|null $cpuPeriod = NULL,
        public float|string|null $cpuRtPeriod = NULL,
        public float|string|null $cpuRtRuntime = NULL,
        public float|string|null $cpus = NULL,
        public string|null $cpuset = NULL,
        public ServiceCredentialSpec|null $credentialSpec = NULL,
        public array|null|ServiceDependsOn $dependsOn = NULL,
        /** @var string|null[]|null */
        public array|null $deviceCgroupRules = NULL,
        /** @var string|null[]|null */
        public array|null $devices = NULL,
        public string|null $dns = NULL,
        /** @var string|null[]|null */
        public array|null $dnsOpt = NULL,
        public string|null $dnsSearch = NULL,
        public string|null $domainname = NULL,
        public null|string|array $entrypoint = NULL,
        public string|null|array $envFile = NULL,
        public string|float|bool|null|array $environment = NULL,
        /** @var string|float|null[]|null */
        public array|null $expose = NULL,
        public string|null|ServiceExtends $extends = NULL,
        /** @var string|null[]|null */
        public array|null $externalLinks = NULL,
        public string|float|bool|null|array $extraHosts = NULL,
        /** @var string|float|null[]|null */
        public array|null $groupAdd = NULL,
        public Healthcheck|null $healthcheck = NULL,
        public string|null $hostname = NULL,
        public string|null $image = NULL,
        public bool|null $init = NULL,
        public string|null $ipc = NULL,
        public string|null $isolation = NULL,
        public string|float|bool|null|array $labels = NULL,
        /** @var string|null[]|null */
        public array|null $links = NULL,
        public ServiceLogging|null $logging = NULL,
        public string|null $macAddress = NULL,
        public float|string|null $memLimit = NULL,
        public string|int|null $memReservation = NULL,
        public int|null $memSwappiness = NULL,
        public float|string|null $memswapLimit = NULL,
        public string|null $networkMode = NULL,
        public array|null|ServiceNetworks $networks = NULL,
        public bool|null $oomKillDisable = NULL,
        public int|null $oomScoreAdj = NULL,
        public string|null $pid = NULL,
        public float|string|null $pidsLimit = NULL,
        public string|null $platform = NULL,
        /** @var float|null|string|ServicePortsItem[]|null */
        public array|null $ports = NULL,
        public bool|null $privileged = NULL,
        /** @var string|null[]|null */
        public array|null $profiles = NULL,
        public ServicePullPolicyEnum|null $pullPolicy = NULL,
        public bool|null $readOnly = NULL,
        public string|null $restart = NULL,
        public string|null $runtime = NULL,
        public int|null $scale = NULL,
        /** @var string|null[]|null */
        public array|null $securityOpt = NULL,
        public float|string|null $shmSize = NULL,
        /** @var string|null|ServiceConfigOrSecretItem[]|null */
        public array|null $secrets = NULL,
        public string|float|bool|null|array $sysctls = NULL,
        public bool|null $stdinOpen = NULL,
        public string|null $stopGracePeriod = NULL,
        public string|null $stopSignal = NULL,
        /** @var array<string, mixed>|null */
        public array|null $storageOpt = NULL,
        public string|null $tmpfs = NULL,
        public bool|null $tty = NULL,
        public int|null|Ulimits $ulimits = NULL,
        public string|null $user = NULL,
        public string|null $uts = NULL,
        public string|null $usernsMode = NULL,
        /** @var string|null|ServiceVolumesItem[]|null */
        public array|null $volumes = NULL,
        /** @var string|null[]|null */
        public array|null $volumesFrom = NULL,
        public string|null $workingDir = NULL
    )
    {
    }
}