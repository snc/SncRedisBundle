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
        php = pkgs.php82.buildEnv {
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
                    rev = "fea19b5229343212424c9921a977fce300d4e130";
                    sha256 = "sha256-1TII8sLDsH9Ufjl0HHtHtBi29FNEG2qNrMkMhM6+iO0=";
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
