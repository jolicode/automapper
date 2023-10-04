# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Added
- [GH#10](https://github.com/jolicode/automapper/pull/10) Introduce custom transformers

## [8.1.0] - 2023-12-14
### Added
- [GH#22](https://github.com/jolicode/automapper/pull/22) Added generic AST extractor
- [GH#21](https://github.com/jolicode/automapper/pull/21) Add VERSION constants within AutoMapper class and use it for transformers hashes
 
### Changed
- [GH#19](https://github.com/jolicode/automapper/pull/19) Use attributes everywhere instead of annotations
- [GH#18](https://github.com/jolicode/automapper/pull/18) Symfony 7 support

## [8.0.2] - 2023-11-06
### Added
- [GH#11](https://github.com/jolicode/automapper/pull/11) Added phpstan level 5 in CI

### Fixed
- [GH#9](https://github.com/jolicode/automapper/pull/9) fix: `mapPrivatePropertiesAndMethod` should not be mandatory
- [GH#15](https://github.com/jolicode/automapper/pull/15) fix: check class existence in SymfonyUidTransformerFactory

## [8.0.1] - 2023-10-04
### Changed
- [GH#6](https://github.com/jolicode/automapper/pull/6) Document all AST code by explaining what it generates

### Fixed
- [GH#7](https://github.com/jolicode/automapper/pull/7) Fix NullableTransformer should check if array key exists

## [8.0.0] - 2023-09-26
### Changed
- Modernization, PHP 8.2 and typed properties everywhere
- [GH#1](https://github.com/jolicode/automapper/pull/1) Better private properties handling

### Fixed
- [GH#753](https://github.com/janephp/janephp/pull/753) Fix: AutoMapper should accept getters with default properties

## [7.5.3] - 2023-08-04
### Fixed
- [GH#742](https://github.com/janephp/janephp/pull/742) Always require registry during cache warmup
- [GH#741](https://github.com/janephp/janephp/pull/741) `#[MapToContext]` should accept virtual properties

## [7.5.2] - 2023-07-10
### Added
- [AutoMapper] [GH#733](https://github.com/janephp/janephp/pull/733) Configure date format with context
- [AutoMapper] [GH#731](https://github.com/janephp/janephp/pull/731) Introduce new attribute `MapToContext`

### Fixed
- [AutoMapper] [GH#734](https://github.com/janephp/janephp/pull/734) Cache warmer should generate mappers for nested classes

## [7.5.1] - 2023-06-13
### Fixed
- [AutoMapper] [GH#729](https://github.com/janephp/janephp/pull/729) Allow to add full objects in `allowed_attribute` context

## [7.5.0] - 2023-04-24
### Changed
- [AutoMapper] [GH#720](https://github.com/janephp/janephp/pull/720) Add mixed return type to generated mappers
- [AutoMapper] [GH#721](https://github.com/janephp/janephp/pull/721) Create mappers on Symfony cache warmup

## [7.4.4] - 2023-04-14
### Added
- [AutoMapper] [GH#710](https://github.com/janephp/janephp/pull/710) Add Enum support in AutoMapper bundle
- [AutoMapper] [GH#711](https://github.com/janephp/janephp/pull/711) Allow nesting properties with `MapperContext::isAllowedAttribute()`
- [AutoMapper] [GH#713](https://github.com/janephp/janephp/pull/713) Use serializer's "ignore" attribute
- [AutoMapper] [GH#714](https://github.com/janephp/janephp/pull/714) Allow custom context in AutomapperNormalizer
- [AutoMapper] [GH#716](https://github.com/janephp/janephp/pull/716) Add readonly properties support
- [AutoMapper] [GH#718](https://github.com/janephp/janephp/pull/718) Disallow readonly target when using object to populate

## [7.4.3] - 2023-03-23
### Added
- [AutoMapper] [GH#707](https://github.com/janephp/janephp/pull/707) Add Enum support

## [7.2.4] - 2022-06-15
### Fixed
- [AutoMapper] [GH#624](https://github.com/janephp/janephp/pull/624) AutoMapper directory creation should be out of registry functions

## [7.2.3] - 2022-06-15
### Changed
- [AutoMapper] [GH#623](https://github.com/janephp/janephp/pull/623) We don't need registry when not hot reloading

## [7.2.2] - 2022-03-21
### Fixed
- [AutoMapper] [GH#606](https://github.com/janephp/janephp/pull/606) Lock file when writing in AutoMapper registry

## [7.1.7] - 2022-02-03
### Fixed
- [AutoMapper] [GH#594](https://github.com/janephp/janephp/pull/594) Issue when no targetTypes in BuiltinTransformer

## [7.1.6] - 2022-01-27
### Fixed
- [AutoMapper] [GH#589](https://github.com/janephp/janephp/pull/589) Fix setting properties when using target to populate object

## [7.1.4] - 2021-12-16
### Fixed
- [AutoMapper] [GH#567](https://github.com/janephp/janephp/pull/567) Fixed MapperContext::withNewContext target_to_populate value

## [7.1.3] - 2021-11-12
### Changed
- [AutoMapper] [GH#564](https://github.com/janephp/janephp/pull/564) Remove deprecations

### Fixed
- [AutoMapper] [GH#567](https://github.com/janephp/janephp/pull/567) Fix the value of `target_to_populate` on `MapperContext::withNewContext` call

## [7.1.2] - 2021-10-18
### Fixed
- [AutoMapper] [GH#560](https://github.com/janephp/janephp/pull/560) Fix fail on generic object without explicit classname

## [7.1.1] - 2021-10-08
### Fixed
- [AutoMapper] [GH#553](https://github.com/janephp/janephp/pull/553) Fix generated Mappers with adder calls

## [7.1.0] - 2021-06-25
### Added
- [AutoMapper] [GH#546](https://github.com/janephp/janephp/pull/546) Add stdClass to stdClass transformation support

### Changed
- [AutoMapper] [GH#536](https://github.com/janephp/janephp/pull/536) Update benchmark scripts

## [7.0.0] - 2021-05-19
### Added
- [AutoMapper] [GH#462](https://github.com/janephp/janephp/pull/462) Move bundle out of the component
- [AutoMapper] [GH#433](https://github.com/janephp/janephp/pull/433) Handle dictionaries with ArrayTransformer
- [AutoMapper] [GH#432](https://github.com/janephp/janephp/pull/432) Ignore API Platform resources when using AutoMapper normalizer
- [AutoMapper] [GH#495](https://github.com/janephp/janephp/pull/495) Add Symfony Uid transformers #495
- [AutoMapper] [GH#507](https://github.com/janephp/janephp/pull/507) Add `skip_null_values` feature

### Changed
- [AutoMapper] [GH#458](https://github.com/janephp/janephp/pull/458) Add PrioritizedTransformerFactoryInterface and implementation
- [AutoMapper] [GH#459](https://github.com/janephp/janephp/pull/459) Add DependentTransformerInterface and implementation
- [AutoMapper] [GH#460](https://github.com/janephp/janephp/pull/460) Add AssignedByReferenceTransformerInterface and implementation

### Fixed
- [AutoMapper] [GH#461](https://github.com/janephp/janephp/pull/461) Transformer arguments typo
- [AutoMapper] [GH#487](https://github.com/janephp/janephp/pull/487)  Can not call getName when type is adders/removers

## [6.3.3] - 2021-02-10
### Changed
- [AutoMapper] [GH#498](https://github.com/janephp/janephp/pull/498) Improve FileLoader: do not use registry at all when hot reload is disabled.
- [AutoMapper] [GH#498](https://github.com/janephp/janephp/pull/498) When using bundle: automatically disable hot reload when not in debug mode.

## [6.3.2] - 2020-12-23
### Changed
- [AutoMapper] [GH#465](https://github.com/janephp/janephp/pull/465) Allow dateTimeFormat customisation when initialising

## [6.3.0] - 2020-11-22
### Added
- [AutoMapper] [GH#443](https://github.com/janephp/janephp/pull/443) Add configuration to use custom NameConverter
- [AutoMapper] [GH#446](https://github.com/janephp/janephp/pull/446) Add autoconfigure on TransformerFactoryInterface
- [AutoMapper] [GH#453](https://github.com/janephp/janephp/pull/453) Introducing autoregistering of custom Mapper configuration

### Changed
- [AutoMapper] [GH#431](https://github.com/janephp/janephp/pull/431) Add a second parameter to `forMember` with target object
- [AutoMapper] [GH#452](https://github.com/janephp/janephp/pull/452) Improve `ClassLoaderInterface` service definition

## [6.2.5] - 2020-11-18
### Fixed
- [AutoMapper] [GH#426](https://github.com/janephp/janephp/pull/426) Fix mapping for empty collection value on an array property

## [6.2.0] - 2020-09-09
### Added
- [AutoMapper] [GH#397](https://github.com/janephp/janephp/pull/397) Update AutoMapper to be able to bind custom TransformerFactory

### Changed
- [AutoMapper] [GH#403](https://github.com/janephp/janephp/pull/403)  Allow ^5.0 for phpdocumentor/reflection-docblock

### Fixed
- [AutoMapper] [GH#396](https://github.com/janephp/janephp/pull/396) Typo in DateTime transformer

## [6.1.0] - 2020-08-20
### Changed
- [AutoMapper] [GH#306](https://github.com/janephp/janephp/pull/306) AutoMapper update

### Fixed
- [AutoMapper] [GH#354](https://github.com/janephp/janephp/pull/354) Do not try to create an instance of an interface
- [AutoMapper] [GH#353](https://github.com/janephp/janephp/pull/353) Allow date_time_format override from bundle
- [AutoMapper] [GH#372](https://github.com/janephp/janephp/pull/372) Use copy transformer for sibling arrays

## [5.3.0] - 2020-01-15
### Added
* [AutoMapper] [GH#214](https://github.com/janephp/janephp/pull/214) Added a quick documentation about the AutoMapper

## [5.2.1] - 2019-11-25
### Fixed
* [AutoMapper] [GH#179](https://github.com/janephp/janephp/pull/179) Fixing incompatible changes in Symfony 5.0

[Unreleased]: https://github.com/jolicode/automapper/compare/8.1.0...HEAD
[8.1.0]: https://github.com/janephp/janephp/compare/8.0.2...8.1.0
[8.0.2]: https://github.com/janephp/janephp/compare/8.0.1...8.0.2
[8.0.1]: https://github.com/janephp/janephp/compare/8.0.0...8.0.1
