---
layout: home

hero:
  name: "Simple Data Objects"
  text: "Typed DTOs for PHP 8.4+"
  tagline: Lightweight, attribute-driven Data Transfer Objects. Works standalone or inside Laravel 10–13.
  image:
    src: https://raw.githubusercontent.com/std-out/simple-data-objects/main/docs/public/logo.svg
    alt: Simple Data Objects
  actions:
    - theme: brand
      text: Get Started
      link: /guide/introduction
    - theme: alt
      text: Quick Start
      link: /guide/quick-start
    - theme: alt
      text: GitHub
      link: https://github.com/std-out/simple-data-objects

features:
  - icon: ⚡
    title: Zero boilerplate
    details: Declare your constructor properties — hydration, serialization, and validation happen automatically via PHP attributes.
  - icon: 🔒
    title: Fully typed
    details: Readonly properties, nested DTOs, enums, typed collections, and IDE-aware generics out of the box.
  - icon: 🎯
    title: Attribute-driven
    details: "#[Cast], #[Rules], #[Flatten], #[Hidden], #[IgnoreIfNull] — all behaviour defined where the property is declared."
  - icon: 🚀
    title: Production-ready performance
    details: Hydration and serialization compile to per-class closures. The file cache plus the sdo-warm CLI give production zero reflection and zero runtime compilation.
  - icon: 🛡️
    title: Secure by default
    details: EncryptedCast uses XSalsa20-Poly1305 authenticated encryption. Validation throws before any hydration occurs.
  - icon: 🔗
    title: Laravel integration
    details: Optional trait adds fromRequest(), fromModel(), and toResponse(). Validation rules run automatically on fromRequest().
---
