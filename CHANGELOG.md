# Change Log

## [3.1.1](https://github.com/snc/SncRedisBundle/tree/3.1.1) (2019-10-09)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/3.1.0...3.1.1)

- Autoconfigure RedisBaseCommand to add snc_redis.command tag (#528) - Maxime Helias
- Simplify injection of client locator using ServiceLocatorTagPass (#535) - Remon van de Kamp
- Fix check for connection_persistent with phpredis factory (#538) - Gijs van Lammeren
- Added simple condition to get rid of Warning: Invalid argument supplied for foreach() (#534) - Adrian Szuszkiewicz
- No longer use curly brackets for substring (#532) - Remon van de Kamp
- Fix a non-existent service "snc_redis.phpredis.monolog" (#531) - Bonn
- Fix C&P error in Phpredis Client Proxy Class - (#526) Jan Ole Behrens

## [3.1.0](https://github.com/snc/SncRedisBundle/tree/3.1.0) (2019-08-01)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/3.0.0...3.1.0)

- Add support for env variable for client type (#525)
- Add metrics to Profiler page (#517)

## [2.1.11](https://github.com/snc/SncRedisBundle/tree/2.1.11) (2019-07-31)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.10...2.1.11)

- fix #523: deprecate alias only (SF 4.3+ required)

## [3.0.0](https://github.com/snc/SncRedisBundle/tree/3.0.0) (2019-07-28)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.10...3.0.0)

- Remove single php 7.3 from travis
- Bump predis minimum version to 1.1
- Add 4.3-dev and 4.4-dev symfony version to CI
- Bump php minimum version to 7.1.3 (Same as Symfony 4)
- fix #249 - fix session handler lock key prefix (#503)
- Fix Swiftmailer version in requirements
- Fix: Explicitly configure Travis build matrix (#474)
- Supply name for new TreeBuilder instead of calling root method (#471)
- Enhancement: Add note about usage with symfony/web-profiler-bundle (#470)
- Fix: No need to verify whether hard dependency is not null (#469)
- Enable Lazy service for phpredis (#440)
- Use RedisDsn to build connection options for env based config (#439)
- Fix support for DSN env variable with phpredis (#432)
- fix #182 token serialization while lock remove (#437)
- fix #419: Create a service locator for clients to be used in the commands. (#433)
- Create cluster connection correctly for one host (#416)
- Fix support for Heroku style REDIS_URL env variables (#413)
- Use destructor to close session (partially reverted #348) (#412)
- Add Serialization option (#411)
- Remove ancient logging facilities
- Properly detect Swiftmailer install
- Check that Doctrine cache configs reference an object manager
- Fix predis logging using symfony cache component
- Make alias configuration default to client name
- Command executions should be logged as DEBUG instead of INFO. In systems where INFO is being used to log informative messages like 'user logged in'.A loop with redis for e.g. in a background-process will pollute the logs extremely.
- Update docs about private services
- Make all bundle services private
- Improve DX when dependencies are missing
- Add PHPUnit 7 support and fix sample config for Symfony 3/4
- Add session fixation note in the update notes
- Default 7.1/7.2 builds use symfony 4.0 so force 3.4 instead of building twice with 4.0
- Implement new Symfony 3.4+ session handler to gain session fixation protection and avoid unnecessary writes

## [2.1.10](https://github.com/snc/SncRedisBundle/tree/2.1.10) (2019-04-09)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.9...2.1.10)

**Merged pull requests:**

- Wrap Parameters in array when using replication - fix \#381 [\#516](https://github.com/snc/SncRedisBundle/pull/516) ([duxet](https://github.com/duxet))
- Execute flushdb/flushall in all nodes of cluster [\#514](https://github.com/snc/SncRedisBundle/pull/514) ([peter-gribanov](https://github.com/peter-gribanov))
- Use semver for PHP version at `composer.json`, added support for PHP 7.3 at Travis [\#508](https://github.com/snc/SncRedisBundle/pull/508) ([phansys](https://github.com/phansys))
- Update method signatures in `Client` in order to respect its parent [\#507](https://github.com/snc/SncRedisBundle/pull/507) ([phansys](https://github.com/phansys))
- Leverage "options.parameters" config in `PhpredisClientFactory::create\(\)` [\#505](https://github.com/snc/SncRedisBundle/pull/505) ([phansys](https://github.com/phansys))
- fix \#249 - fix session handler lock key prefix [\#503](https://github.com/snc/SncRedisBundle/pull/503) ([B-Galati](https://github.com/B-Galati))
- fix \#383 - deprecates redis service alias in favor of 'snc\_redis.{alias}' [\#501](https://github.com/snc/SncRedisBundle/pull/501) ([B-Galati](https://github.com/B-Galati))

## [2.1.9](https://github.com/snc/SncRedisBundle/tree/2.1.9) (2019-02-20)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.8...2.1.9)

**Merged pull requests:**

- fix \#498 - Add read\_timeout connection option to phpredis [\#499](https://github.com/snc/SncRedisBundle/pull/499) ([B-Galati](https://github.com/B-Galati))
- Not quoting the % indicator is deprecated [\#495](https://github.com/snc/SncRedisBundle/pull/495) ([magnetik](https://github.com/magnetik))
-  Fix \#312 - deprecation option cannot be false  [\#494](https://github.com/snc/SncRedisBundle/pull/494) ([B-Galati](https://github.com/B-Galati))
- Fix \#449 - No master server available for replication [\#493](https://github.com/snc/SncRedisBundle/pull/493) ([B-Galati](https://github.com/B-Galati))

## [2.1.8](https://github.com/snc/SncRedisBundle/tree/2.1.8) (2019-02-04)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.7...2.1.8)

**Merged pull requests:**

- No memory limit for composer in TravisCI [\#492](https://github.com/snc/SncRedisBundle/pull/492) ([B-Galati](https://github.com/B-Galati))
- Fix TravisCI after merging 2.1 into master [\#491](https://github.com/snc/SncRedisBundle/pull/491) ([B-Galati](https://github.com/B-Galati))
- set logger on the connection wrapper only [\#489](https://github.com/snc/SncRedisBundle/pull/489) ([xabbuh](https://github.com/xabbuh))
- Conditionally enable lazy loading for `phpredis` [\#487](https://github.com/snc/SncRedisBundle/pull/487) ([rvanlaak](https://github.com/rvanlaak))
- Excluded tests from classmap [\#486](https://github.com/snc/SncRedisBundle/pull/486) ([samnela](https://github.com/samnela))
- Fix: Make script step explicit [\#477](https://github.com/snc/SncRedisBundle/pull/477) ([localheinz](https://github.com/localheinz))
- Enhancement: Normalize composer.json [\#475](https://github.com/snc/SncRedisBundle/pull/475) ([localheinz](https://github.com/localheinz))
- Fix: Explicitly configure Travis build matrix [\#474](https://github.com/snc/SncRedisBundle/pull/474) ([localheinz](https://github.com/localheinz))
- Fix: Remove useless else [\#473](https://github.com/snc/SncRedisBundle/pull/473) ([localheinz](https://github.com/localheinz))
- Fix: Combine conditions [\#472](https://github.com/snc/SncRedisBundle/pull/472) ([localheinz](https://github.com/localheinz))
- Supply name for new TreeBuilder instead of calling root method [\#471](https://github.com/snc/SncRedisBundle/pull/471) ([rpkamp](https://github.com/rpkamp))
- Enhancement: Add note about usage with symfony/web-profiler-bundle [\#470](https://github.com/snc/SncRedisBundle/pull/470) ([localheinz](https://github.com/localheinz))
- Fix: No need to verify whether hard dependency is not null [\#469](https://github.com/snc/SncRedisBundle/pull/469) ([localheinz](https://github.com/localheinz))
- Fix: Travis CI badge URLs [\#468](https://github.com/snc/SncRedisBundle/pull/468) ([localheinz](https://github.com/localheinz))
- Add Symfony 4.1, 4.2 and php nightly to the CI [\#450](https://github.com/snc/SncRedisBundle/pull/450) ([B-Galati](https://github.com/B-Galati))

## [2.1.7](https://github.com/snc/SncRedisBundle/tree/2.1.7) (2018-10-15)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.6...2.1.7)

**Merged pull requests:**

- \[Fix\]\[Predis\] Fixes persistent connections when used many databases on the same instance [\#462](https://github.com/snc/SncRedisBundle/pull/462) ([qRoC](https://github.com/qRoC))
- Fix connection via TLS \(rediss://\) \(\#444\) [\#445](https://github.com/snc/SncRedisBundle/pull/445) ([jankramer](https://github.com/jankramer))

## [2.1.6](https://github.com/snc/SncRedisBundle/tree/2.1.6) (2018-07-31)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.5...2.1.6)

## [2.1.5](https://github.com/snc/SncRedisBundle/tree/2.1.5) (2018-07-18)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.4...2.1.5)

**Merged pull requests:**

- \#434 Enable Lazy service for phpredis [\#440](https://github.com/snc/SncRedisBundle/pull/440) ([B-Galati](https://github.com/B-Galati))

## [2.1.4](https://github.com/snc/SncRedisBundle/tree/2.1.4) (2018-06-25)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.3...2.1.4)

**Merged pull requests:**

- \#425 Use RedisDsn to build connection options for env based config  [\#439](https://github.com/snc/SncRedisBundle/pull/439) ([B-Galati](https://github.com/B-Galati))
-  \#182 token serialization while lock remove [\#437](https://github.com/snc/SncRedisBundle/pull/437) ([piotrkochan](https://github.com/piotrkochan))
- \#419: Create a service locator for clients to be used in the commands. [\#433](https://github.com/snc/SncRedisBundle/pull/433) ([Basster](https://github.com/Basster))
- \#428 \#356 Fix support for DSN env variable with phpredis [\#432](https://github.com/snc/SncRedisBundle/pull/432) ([B-Galati](https://github.com/B-Galati))

## [2.1.3](https://github.com/snc/SncRedisBundle/tree/2.1.3) (2018-05-09)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.2...2.1.3)

**Merged pull requests:**

- Remove env placeholder regexp [\#422](https://github.com/snc/SncRedisBundle/pull/422) ([B-Galati](https://github.com/B-Galati))
- Create cluster connection correctly for one host [\#416](https://github.com/snc/SncRedisBundle/pull/416) ([linasm83](https://github.com/linasm83))
- Close connections on shutdown [\#415](https://github.com/snc/SncRedisBundle/pull/415) ([supersmile2009](https://github.com/supersmile2009))
- Fix support for Heroku style REDIS\_URL env variables [\#413](https://github.com/snc/SncRedisBundle/pull/413) ([B-Galati](https://github.com/B-Galati))

## [2.1.2](https://github.com/snc/SncRedisBundle/tree/2.1.2) (2018-04-23)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.1...2.1.2)

## [2.1.1](https://github.com/snc/SncRedisBundle/tree/2.1.1) (2018-04-18)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.0...2.1.1)

**Merged pull requests:**

- Use destructor to close session \(partially reverted \#348\) [\#412](https://github.com/snc/SncRedisBundle/pull/412) ([supersmile2009](https://github.com/supersmile2009))
- Add Serialization option [\#411](https://github.com/snc/SncRedisBundle/pull/411) ([yellow1912](https://github.com/yellow1912))
- Make all bundle services private [\#409](https://github.com/snc/SncRedisBundle/pull/409) ([curry684](https://github.com/curry684))
- fix predis logging using symfony cache component [\#408](https://github.com/snc/SncRedisBundle/pull/408) ([vchebotarev](https://github.com/vchebotarev))
- New session handler [\#404](https://github.com/snc/SncRedisBundle/pull/404) ([Seldaek](https://github.com/Seldaek))

## [2.1.0](https://github.com/snc/SncRedisBundle/tree/2.1.0) (2018-04-06)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.0.6...2.1.0)

**Merged pull requests:**

- Disable logging wrappers on PhpRedis \>= 4.0.0 [\#405](https://github.com/snc/SncRedisBundle/pull/405) ([curry684](https://github.com/curry684))
- Use a Predis command to be able to use EVALSHA instead of EVALing the session freeing script every time [\#403](https://github.com/snc/SncRedisBundle/pull/403) ([Seldaek](https://github.com/Seldaek))
- Clarify documentation on Redis Sentinel [\#398](https://github.com/snc/SncRedisBundle/pull/398) ([rpkamp](https://github.com/rpkamp))
- Pimp the readme with badge poser [\#396](https://github.com/snc/SncRedisBundle/pull/396) ([JellyBellyDev](https://github.com/JellyBellyDev))
- No longer use style in svg image for profiler [\#395](https://github.com/snc/SncRedisBundle/pull/395) ([rpkamp](https://github.com/rpkamp))
- Resolve env placeholders for profile [\#393](https://github.com/snc/SncRedisBundle/pull/393) ([Majkl578](https://github.com/Majkl578))
- Ignore bin folder, use absolute ignore paths [\#392](https://github.com/snc/SncRedisBundle/pull/392) ([Majkl578](https://github.com/Majkl578))
- Updated test with namespaced PHPUnit TestCase [\#388](https://github.com/snc/SncRedisBundle/pull/388) ([MarioBlazek](https://github.com/MarioBlazek))
- Update .travis.yml [\#386](https://github.com/snc/SncRedisBundle/pull/386) ([andreybolonin](https://github.com/andreybolonin))
- Change lockMaxWait in RedisSessionHandler from private to protected [\#380](https://github.com/snc/SncRedisBundle/pull/380) ([lunglung876](https://github.com/lunglung876))
- Support env vars for both drivers [\#378](https://github.com/snc/SncRedisBundle/pull/378) ([kozlice](https://github.com/kozlice))
- dont re-define the function in the loop again and again [\#355](https://github.com/snc/SncRedisBundle/pull/355) ([staabm](https://github.com/staabm))
- Session handler shutdown cleanup [\#348](https://github.com/snc/SncRedisBundle/pull/348) ([coder-pm](https://github.com/coder-pm))
- Call logCommand\(\) on null [\#335](https://github.com/snc/SncRedisBundle/pull/335) ([Jim-Raynor](https://github.com/Jim-Raynor))
- Update RateLimit.php to correct post URL [\#287](https://github.com/snc/SncRedisBundle/pull/287) ([overint](https://github.com/overint))
- IPv6 support [\#147](https://github.com/snc/SncRedisBundle/pull/147) ([Quidle](https://github.com/Quidle))

## [2.0.6](https://github.com/snc/SncRedisBundle/tree/2.0.6) (2017-12-01)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.0.5...2.0.6)

## [2.0.5](https://github.com/snc/SncRedisBundle/tree/2.0.5) (2017-12-01)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.0.4...2.0.5)

**Merged pull requests:**

- Fix ci warning caused by PHPUnit Api deprecation [\#373](https://github.com/snc/SncRedisBundle/pull/373) ([SiM07](https://github.com/SiM07))
- Provides Symfony 4 compatibility [\#370](https://github.com/snc/SncRedisBundle/pull/370) ([ghost](https://github.com/ghost))
- forward compatibility with Symfony 4.0 [\#366](https://github.com/snc/SncRedisBundle/pull/366) ([xabbuh](https://github.com/xabbuh))

## [2.0.4](https://github.com/snc/SncRedisBundle/tree/2.0.4) (2017-10-02)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.0.3...2.0.4)

## [2.0.3](https://github.com/snc/SncRedisBundle/tree/2.0.3) (2017-10-01)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.0.2...2.0.3)

**Merged pull requests:**

- Support for Heroku style REDIS\_URL env variables [\#353](https://github.com/snc/SncRedisBundle/pull/353) ([iKlaus](https://github.com/iKlaus))
- Fix RedisSpool to use Swift\_Mime\_SimpleMessage [\#350](https://github.com/snc/SncRedisBundle/pull/350) ([gohiei](https://github.com/gohiei))

## [2.0.2](https://github.com/snc/SncRedisBundle/tree/2.0.2) (2017-06-15)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.0.1...2.0.2)

**Merged pull requests:**

- Fix template namespacing [\#349](https://github.com/snc/SncRedisBundle/pull/349) ([alOneh](https://github.com/alOneh))
- Improve the readme [\#342](https://github.com/snc/SncRedisBundle/pull/342) ([stof](https://github.com/stof))
- Move the LICENSE file to the root [\#341](https://github.com/snc/SncRedisBundle/pull/341) ([stof](https://github.com/stof))
- Add database & password parameters support for predis sentinel [\#340](https://github.com/snc/SncRedisBundle/pull/340) ([Erliz](https://github.com/Erliz))
- Added prefix option for client in readme [\#338](https://github.com/snc/SncRedisBundle/pull/338) ([stellalie](https://github.com/stellalie))
- Fixed usage of non-Twig paths [\#337](https://github.com/snc/SncRedisBundle/pull/337) ([stellalie](https://github.com/stellalie))
- Create .gitattributes [\#309](https://github.com/snc/SncRedisBundle/pull/309) ([Aliance](https://github.com/Aliance))
- allow sentinel replication configuration [\#307](https://github.com/snc/SncRedisBundle/pull/307) ([othillo](https://github.com/othillo))
- Pass the path parameter to deduplicate persistent connections [\#186](https://github.com/snc/SncRedisBundle/pull/186) ([dominics](https://github.com/dominics))

## [2.0.1](https://github.com/snc/SncRedisBundle/tree/2.0.1) (2017-02-16)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.0.0...2.0.1)

**Merged pull requests:**

- Fixed last pull request according comments [\#325](https://github.com/snc/SncRedisBundle/pull/325) ([aurimasniekis](https://github.com/aurimasniekis))
- \#323 Implemented error handling for PHPRedis/Client::call [\#324](https://github.com/snc/SncRedisBundle/pull/324) ([aurimasniekis](https://github.com/aurimasniekis))
- Removed unused code line in RedisLogger.php file [\#322](https://github.com/snc/SncRedisBundle/pull/322) ([aurimasniekis](https://github.com/aurimasniekis))
- As per the ticket \#318, reverting the odm definition change [\#319](https://github.com/snc/SncRedisBundle/pull/319) ([usmanzafar](https://github.com/usmanzafar))
- update scan to use reference \(iterator\) [\#316](https://github.com/snc/SncRedisBundle/pull/316) ([toooni](https://github.com/toooni))
- Use persistent id generated from DSN, if connection\_persistent is set to 'true' [\#308](https://github.com/snc/SncRedisBundle/pull/308) ([Donar23](https://github.com/Donar23))
- Fix small typo in index.md [\#303](https://github.com/snc/SncRedisBundle/pull/303) ([chteuchteu](https://github.com/chteuchteu))
- Reimplementation of the RedisProfilerStorage [\#301](https://github.com/snc/SncRedisBundle/pull/301) ([GijsL](https://github.com/GijsL))
- Use .svg image instead of .png for Travis badges [\#298](https://github.com/snc/SncRedisBundle/pull/298) ([bocharsky-bw](https://github.com/bocharsky-bw))
- clarify docs around urlencoding chars in password [\#292](https://github.com/snc/SncRedisBundle/pull/292) ([danalloway](https://github.com/danalloway))
- Update the maintenance status badge [\#288](https://github.com/snc/SncRedisBundle/pull/288) ([emirb](https://github.com/emirb))



\* *This Change Log was automatically generated by [github_changelog_generator](https://github.com/skywinder/Github-Changelog-Generator)*
