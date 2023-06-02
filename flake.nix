{
  inputs = {
    nixpkgs.url = "github:NixOS/nixpkgs/nixpkgs-unstable";
  };

  outputs = {
    self,
    nixpkgs,
  }: let
  in {
    formatter.aarch64-darwin = nixpkgs.legacyPackages.aarch64-darwin.alejandra;
    buildEnv = {
      pkgs,
      relay,
    }:
      pkgs.buildEnv {
        name = "snc-redis";
        paths = let
          php = pkgs.php82;
        in [
          php.packages.composer
          pkgs.redis
          pkgs.overmind
          (
            php.withExtensions
            (
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
            )
          )
        ];
      };
    packages = {
      aarch64-darwin.default = self.buildEnv rec {
        pkgs = nixpkgs.legacyPackages.aarch64-darwin;
        relay = {
          platform = "darwin-arm64";
          sha256 = "0xhg1z4gnifiy6ra76qrc1m0wi8gg6f9kgn9dnw6x5343p3v8k71";
        };
      };
      aarch64-linux.default = self.buildEnv {
        pkgs = nixpkgs.legacyPackages.aarch64-linux;
        relay = {
          platform = "debian-aarch64+libssl3";
          sha256 = "1yn2cldz0fy5p3dyfykyrnra969hb858wib205ykhac1plsq1p0j";
        };
      };
      x86_64-darwin.default = self.buildEnv {
        pkgs = nixpkgs.legacyPackages.x86_64-darwin;
        relay = {
          platform = "darwin-x86-64";
          sha256 = "0jp3r1kbcnhiyah7nmvh0hmwdrcbj5i5bdz2l22z3vk3sg33izr7";
        };
      };
      x86_64-linux.default = self.buildEnv {
        pkgs = nixpkgs.legacyPackages.x86_64-linux;
        relay = {
          platform = "debian-x86-64+libssl3";
          sha256 = "0l58qswv4acdwrg9cvb0zpm8im01yz1pqx02b39ya64qd1vwrjl2";
        };
      };
    };
  };
}
