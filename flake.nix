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
                  (all.redis.overrideAttrs (finalAttrs: previousAttrs: {
                    src = pkgs.fetchFromGitHub {
                      repo = "phpredis";
                      owner = "phpredis";
                      rev = "2b2bc042da712677118d82608a3b559188a66219";
                      sha256 = "sha256-j6WLOHfthLOHNHOyhcIpYEbFqoGknABouZbYQTc4GyE";
                    };
                  }))
                  # relay section https://relay.so/docs/1.x/installation#manual-installation
                  all.igbinary
                  all.msgpack
                  (
                    pkgs.stdenv.mkDerivation {
                      name = "relay";
                      extensionName = "relay";
                      src = builtins.fetchTarball {
                        url =
                          "https://builds.r2.relay.so/v${relayVersion}/relay-v${relayVersion}-php"
                          + (pkgs.lib.versions.majorMinor php.version)
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
                          then let
                            n = pkgs.lib.attrsets.nameValuePair;
                            s = pkgs.lib.strings;
                            args = s.concatMapStrings (v: " -change /Users/administrator/dev/relay-dev/relay-deps/build/arm64/lib/${v.name} ${s.makeLibraryPath [v.value]}/${v.name}") (with pkgs; [
                              (n "libssl.1.1.dylib" openssl_1_1)
                              (n "libcrypto.1.1.dylib" openssl_1_1)
                              (n "libzstd.1.dylib" zstd)
                              (n "liblz4.1.dylib" lz4)
                            ]);
                          in ''
                            install_name_tool${args} $out/lib/php/extensions/relay.so
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
