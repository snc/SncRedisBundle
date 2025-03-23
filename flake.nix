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
                    rev = "300c5fb218ebb55fb6eca4de91756a91e57912ea";
                    sha256 = "sha256-ScUVyzd7GG33oNCXl1dqBd4m4xYK2fYHHbPNGu/Hl6c=";
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
