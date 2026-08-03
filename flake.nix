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
                    rev = "dd15a7dd90738e840ce43b5815ddbff7fe6d123b";
                    sha256 = "sha256-xndHEcNt8cQZ2fwcGSxeoJv/MkBprwmjR8KiuxqjpUI=";
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
