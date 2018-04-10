# Change Log

## [Unreleased](https://github.com/snc/SncRedisBundle/tree/HEAD)

[Full Changelog](https://github.com/snc/SncRedisBundle/compare/2.1.0...HEAD)

**Merged pull requests:**

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
- Provides Symfony 4 compatibility [\#370](https://github.com/snc/SncRedisBundle/pull/370) ([romain-pierre](https://github.com/romain-pierre))
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