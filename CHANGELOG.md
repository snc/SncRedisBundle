# Change Log

## [3.0.0](https://github.com/snc/SncRedisBundle/tree/3.0.0) (2019-01-17)
[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.7...3.0.0)

**Merged pull requests:**

- No memory limit for composer in TravisCI [\#492](https://github.com/snc/SncRedisBundle/pull/492) ([B-Galati](https://github.com/B-Galati))
- Fix TravisCI after merging 2.1 into master [\#491](https://github.com/snc/SncRedisBundle/pull/491) ([B-Galati](https://github.com/B-Galati))
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
- Provides Symfony 4 compatibility [\#370](https://github.com/snc/SncRedisBundle/pull/370) ([symcaster](https://github.com/symcaster))
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
