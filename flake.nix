{
  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";
    flake-utils.url = "github:numtide/flake-utils";
  };

  outputs = {
    self,
    nixpkgs,
    flake-utils,
  } @ inputs:
    flake-utils.lib.eachDefaultSystem
    (
      system: let
        pkgs = import nixpkgs {
          inherit system;
          config.allowUnfree = true;
          config.allowInsecurePredicate = pkg: pkgs.lib.getName pkg == "openssl";
        };
        php = pkgs.php84.buildEnv {
          extensions = (
            {
              all,
              enabled,
            }:
              enabled
              ++ [
                all.xdebug
                (all.redis.overrideAttrs (finalAttrs: previousAttrs: {
                  src = pkgs.fetchFromGitHub {
                    repo = "phpredis";
                    owner = "phpredis";
                    rev = "a6313d1bda6fb25bc53d8ef3b6d7dd67e05503aa";
                    sha256 = "sha256-PCTuaOkqGkydyIqU9EuHwRKnmoi81QK5Tm9o5vmbGYk=";
                  };
                }))
                # relay section https://relay.so/docs/1.x/installation#manual-installation
                all.igbinary
                all.msgpack
                all.relay
              ]
          );
        };
      in {
        formatter = pkgs.alejandra;
        packages.default = pkgs.buildEnv {
          name = "snc-redis";
          paths = [
            php
            php.packages.composer
            pkgs.redis
            pkgs.overmind
          ];
        };
      }
    );
}
