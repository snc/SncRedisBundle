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
          relayVersion = "0.6.1";
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
                  (php.buildPecl {
                    version = "dev";
                    pname = "redis";
                    src = pkgs.fetchFromGitHub {
                      repo = "phpredis";
                      owner = "phpredis";
                      rev = "2b2bc042da712677118d82608a3b559188a66219";
                      sha256 = "sha256-j6WLOHfthLOHNHOyhcIpYEbFqoGknABouZbYQTc4GyE";
                    };
                    internalDeps = [php.extensions.session];
                  })
                  # relay section https://relay.so/docs/1.x/installation#manual-installation
                  all.igbinary
                  (
                    php.buildPecl {
                      version = "2.2.0RC2";
                      pname = "msgpack";
                      sha256 = "sha256-bVV043knbk7rionXqB70RKa1zlJ5K/Nw0oTXZllmJOg=";
                    }
                  )
                  (
                    pkgs.stdenv.mkDerivation {
                      name = "relay";
                      extensionName = "relay";
                      src = builtins.fetchTarball {
                        url =
                          "https://builds.r2.relay.so/v${relayVersion}/relay-v${relayVersion}-php"
                          + (builtins.substring 0 3 php.version)
                          + "-"
                          + relay.platform
                          + ".tar.gz";
                        sha256 = relay.sha256;
                      };
                      installPhase =
                        ''
                          mkdir -p $out/lib/php/extensions
                          cp $src/relay-pkg.so $out/lib/php/extensions/relay.so
                          chmod +w $out/lib/php/extensions/relay.so
                        ''
                        + (
                          if pkgs.stdenv.isDarwin
                          then ''
                            install_name_tool \
                                -change /Users/administrator/dev/relay-dev/relay-deps/build/arm64/lib/libssl.1.1.dylib ${pkgs.openssl_1_1.out}/lib/libssl.1.1.dylib \
                                -change /Users/administrator/dev/relay-dev/relay-deps/build/arm64/lib/libcrypto.1.1.dylib ${pkgs.openssl_1_1.out}/lib/libcrypto.1.1.dylib \
                                -change /Users/administrator/dev/relay-dev/relay-deps/build/arm64/lib/libzstd.1.dylib ${pkgs.zstd.out}/lib/libzstd.1.dylib \
                                -change /Users/administrator/dev/relay-dev/relay-deps/build/arm64/lib/liblz4.1.dylib ${pkgs.lz4.out}/lib/liblz4.1.dylib \
                                $out/lib/php/extensions/relay.so
                          ''
                          else ""
                        )
                        + ''
                          sed -i "s/00000000-0000-0000-0000-000000000000/6f70cad7-4e83-4c25-be90-0812eb50302e/" $out/lib/php/extensions/relay.so
                          chmod -w $out/lib/php/extensions/relay.so
                        '';
                    }
                  )
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
