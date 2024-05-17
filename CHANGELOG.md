# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]
### Deprecated
- [GH#136](https://github.com/jolicode/automapper/pull/136) Deprecate the ability to inject AST transformer factories withing stand-alone AutoMapper

### Fixed
- [GH#131](https://github.com/jolicode/automapper/pull/131) Require mandatory packages
- [GH#132](https://github.com/jolicode/automapper/pull/132) Use DI Extension class instead of deprecated HttpKernel Extension
- [GH#130](https://github.com/jolicode/automapper/pull/130) Make ClassDiscriminatorResolver optional
- [GH#135](https://github.com/jolicode/automapper/pull/135) Fix return type of AutoMapper::create()
- [GH#139](https://github.com/jolicode/automapper/pull/139) Fix unreachable variable in BuitinTransformer
- [GH#138](https://github.com/jolicode/automapper/pull/138) Declare CopyTransformerFactory as a service
- [GH#142](https://github.com/jolicode/automapper/pull/142) Make MapperMetadata non-internal because it is used within PropertyTransformerSupportInterface interface
- [GH#148](https://github.com/jolicode/automapper/pull/148) Handle deprecated class LNumber in nikic/php-parser v5
- [GH#151](https://github.com/jolicode/automapper/pull/151) Fix type in generated code

## [9.0.1] - 2024-05-10
### Fixed
- [GH#124](https://github.com/jolicode/automapper/pull/124) Fix Symfony's WebProfiler issues
- [GH#125](https://github.com/jolicode/automapper/pull/125) Fix MetadataCollector default highlight colors

## [9.0.0] - 2024-05-06
### Added
- [GH#114](https://github.com/jolicode/automapper/pull/114) Introducing Mapper Attribute
- [GH#117](https://github.com/jolicode/automapper/pull/117) Allow multiple source/target, allow overriding attribute with priority system

### Changed
- [GH#119](https://github.com/jolicode/automapper/pull/119) Change serializer configuration naming in Symfony Bundle

### Fixed
- [GH#109](https://github.com/jolicode/automapper/pull/109) Use AutoMapper exceptions
- [GH#115](https://github.com/jolicode/automapper/pull/115) Fix generating discriminator dependencies
- [GH#116](https://github.com/jolicode/automapper/pull/116) Fix property transformer with adder and remover methods

## [9.0.0-beta.2] - 2024-04-02
### Added
- [GH#95](https://github.com/jolicode/automapper/pull/95) Add Api Platform integration
- [GH#103](https://github.com/jolicode/automapper/pull/103) Add debug command and profiler for the symfony bundle

### Changed
- [GH#104](https://github.com/jolicode/automapper/pull/104) Replace allow_constructor with constructor_strategy to have more control on how to use the constructor
- [GH#102](https://github.com/jolicode/automapper/pull/102) Change default reload behavior for the symfony bundle

### Fixed
- [GH#101](https://github.com/jolicode/automapper/pull/101) Fix some inconsistencies with symfony/serializer behavior

## [9.0.0-beta.1] - 2024-03-25
### Added
- [GH#61](https://github.com/jolicode/automapper/pull/61) Add event system during code generation, make serializer optional thanks to it
- [GH#63](https://github.com/jolicode/automapper/pull/63) Merge bundle directly into automapper, will replace automapper-bundle
- [GH#59](https://github.com/jolicode/automapper/pull/59) Add MapTo & MapFrom attributes
- [GH#78](https://github.com/jolicode/automapper/pull/78) Add MapTo & MapFrom listeners to bundle
- [GH#80](https://github.com/jolicode/automapper/pull/80) Add if feature to MapTo / MapFrom attributes
- [GH#81](https://github.com/jolicode/automapper/pull/81) Allow MapTo / MapFrom attributes in class when declaring a transformer and a name
- [GH#82](https://github.com/jolicode/automapper/pull/82) Add groups to MapTo / MapFrom attributes
- [GH#84](https://github.com/jolicode/automapper/pull/84) Allow expression language for transformer and add provider for custom functions
- [GH#86](https://github.com/jolicode/automapper/pull/86) Bundle: Allow to use eval loader instead of file
- [GH#89](https://github.com/jolicode/automapper/pull/89) Add normalizer format in context, allow skipping group checking and remove registry interface from normalizer
- [GH#96](https://github.com/jolicode/automapper/pull/96) Add a way to instantiate the target object from external service using provider
- [GH#98](https://github.com/jolicode/automapper/pull/98) Allow normalizer to only work with registered mapping

### Changed
- [GH#56](https://github.com/jolicode/automapper/pull/56) Refactor metadata
- [GH#68](https://github.com/jolicode/automapper/pull/68) Allow to use sf 5.4 for most things, remove useless deps / suggests
- [GH#71](https://github.com/jolicode/automapper/pull/71) Use interface for class metadata factory
- [GH#75](https://github.com/jolicode/automapper/pull/75) Types: better matching between types to better handle multiple types
- [GH#79](https://github.com/jolicode/automapper/pull/79) Refactor the way to create custom transformer
- [GH#90](https://github.com/jolicode/automapper/pull/90) Allow to run symfony app in tests

### Fixed
- [GH#70](https://github.com/jolicode/automapper/pull/70) Split map to / map from, fix from array
- [GH#73](https://github.com/jolicode/automapper/pull/73) Don't map unexisting context, fix target to populate
- [GH#72](https://github.com/jolicode/automapper/pull/72) Don't map property if groups specified and no groups attached
- [GH#74](https://github.com/jolicode/automapper/pull/74) Check class exists when checking reflection class in object transformer factory
- [GH#77](https://github.com/jolicode/automapper/pull/77) Try to get types from read accessor / write mutator first
- [GH#83](https://github.com/jolicode/automapper/pull/83) Fix MapFrom: correctly use method from target if asked
- [GH#85](https://github.com/jolicode/automapper/pull/85) Fix mapping with proxies
- [GH#85](https://github.com/jolicode/automapper/pull/85) Fix \Traversable normalization
- [GH#85](https://github.com/jolicode/automapper/pull/85) Fix array fetching on string indexed array
- [GH#87](https://github.com/jolicode/automapper/pull/87) Correctly map from an inherited class
- [GH#93](https://github.com/jolicode/automapper/pull/93) Fix map to overriding ignore / groups attribute from serializer
- [GH#94](https://github.com/jolicode/automapper/pull/94) Fix most phpstan issues

### Documentation
- [GH#91](https://github.com/jolicode/automapper/pull/91) Add documentation versioning
- [GH#88](https://github.com/jolicode/automapper/pull/88) Reorganize documentation structure
- [GH#92](https://github.com/jolicode/automapper/pull/92) Add doc about mapping
- [GH#98](https://github.com/jolicode/automapper/pull/98) Add migration guide into documentation

## [8.2.2] - 2024-03-19
### Added
- [GH#54](https://github.com/jolicode/automapper/pull/54) Introduce `MapperContext::DATETIME_FORCE_TIMEZONE`

### Fixed
- [GH#55](https://github.com/jolicode/automapper/pull/55) Remove most of deprecations in tests
- [GH#69](https://github.com/jolicode/automapper/pull/69) Allow to handle union types with several objects

### Documentation
- [GH#64](https://github.com/jolicode/automapper/pull/64) Use poetry to run and build documentation

## [8.2.1] - 2024-03-11
### Changed
- [GH#50](https://github.com/jolicode/automapper/pull/50) Support generator mapping
- [GH#36](https://github.com/jolicode/automapper/pull/36) Compatibility with nikic/php-parser v5

### Fixed
- [GH#52](https://github.com/jolicode/automapper/pull/52) Don't tell we support internal php classes

## [8.2.0] - 2024-03-11
### Added
- [GH#25](https://github.com/jolicode/automapper/pull/25) Pass full input object to property custom transformers
- [GH#10](https://github.com/jolicode/automapper/pull/10) Introduce custom transformers
- [GH#26](https://github.com/jolicode/automapper/pull/26) Fix mappings involving DateTimeInterface type
- [GH#37](https://github.com/jolicode/automapper/pull/37) Adds useful phpDoc annotation in generated mappers

### Changed
- [GH#27](https://github.com/jolicode/automapper/pull/27) Use PhpStanExtractor instead of PhpDocExtractor
- [GH#35](https://github.com/jolicode/automapper/pull/35) Refactoring Mapper Generator
- [GH#47](https://github.com/jolicode/automapper/pull/47) Use directly the custom transformer instead of extracting it as a callback
- [GH#48](https://github.com/jolicode/automapper/pull/48) Change the way transformer factory are injected to make it work as soon as automapper is created

### Fixed
- [GH#33](https://github.com/jolicode/automapper/pull/33) Allow usage of imported class names in custom transformers
- [GH#45](https://github.com/jolicode/automapper/pull/45) Fix composer.lock for phpstan and php-cs-fixer tooling
- [GH#44](https://github.com/jolicode/automapper/pull/44) Allow skipping uninitialized property when skipping null values

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

[Unreleased]: https://github.com/jolicode/automapper/compare/9.0.1...HEAD
[9.0.1]: https://github.com/janephp/janephp/compare/9.0.0...9.0.1
[9.0.0]: https://github.com/janephp/janephp/compare/9.0.0-beta.2...9.0.0
[9.0.0-beta.2]: https://github.com/janephp/janephp/compare/9.0.0-beta.1...9.0.0-beta.2
[9.0.0-beta.1]: https://github.com/janephp/janephp/compare/8.2.2...9.0.0-beta.1
[8.2.2]: https://github.com/janephp/janephp/compare/8.2.1...8.2.2
[8.2.1]: https://github.com/janephp/janephp/compare/8.2.0...8.2.1
[8.2.0]: https://github.com/janephp/janephp/compare/8.1.0...8.2.0
[8.1.0]: https://github.com/janephp/janephp/compare/8.0.2...8.1.0
[8.0.2]: https://github.com/janephp/janephp/compare/8.0.1...8.0.2
[8.0.1]: https://github.com/janephp/janephp/compare/8.0.0...8.0.1
