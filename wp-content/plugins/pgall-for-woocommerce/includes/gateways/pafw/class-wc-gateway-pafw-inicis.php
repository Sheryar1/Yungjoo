<?php


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WC_Gateway_PAFW_Inicis' ) ) {

	include_once( 'class-wc-gateway-pafw.php' );
	class WC_Gateway_PAFW_Inicis extends WC_Gateway_PAFW {
		public function __construct() {
			$this->id = 'mshop_inicis';

			$this->init_settings();

			$this->title              = __( 'KG 이니시스', 'pgall-for-woocommerce' );
			$this->method_title       = __( 'KG 이니시스', 'pgall-for-woocommerce' );
			$this->method_description = '<div style="font-size: 0.9em;">이니시스 일반결제 및 간편결제를 이용합니다. (신용카드, 실시간 계좌이체, 가상계좌, KPAY 간편결제, 삼성페이, 휴대폰 소액결제, 에스크로)</div>';

			parent::__construct();
		}
		public static function get_supported_payment_methods() {
			return array (
				'inicis_stdcard'        => '신용카드',
				'inicis_stdbank'        => '실시간 계좌이체',
				'inicis_stdvbank'       => '가상계좌',
				'inicis_stdkpay'        => 'KPAY 간편결제',
				'inicis_stdhpp'         => '휴대폰 소액결제',
				'inicis_stdescrow_bank' => '에스크로',
				'inicis_stdsamsungpay'  => '삼성페이'
			);
		}
		public function admin_options() {

			parent::admin_options();

			$options = get_option( 'pafw_mshop_inicis' );

			$GLOBALS['hide_save_button'] = 'yes' != pafw_get( $options, 'show_save_button', 'no' );

			$settings = $this->get_settings( 'inicis', self::get_supported_payment_methods() );

			$this->enqueue_script();
			wp_localize_script( 'mshop-setting-manager', 'mshop_setting_manager', array (
				'element'  => 'mshop-setting-wrapper',
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'action'   => PAFW()->slug() . '-update_inicis_settings',
				'settings' => $settings
			) );

			//키파일 업로드 처리
			if ( isset( $_FILES ) && ! empty( $_FILES ) ) {
				$this->keyfile_upload_process();
				?>
                <script type="text/javascript">
                    window.location.reload();
                </script>
				<?php
			}

			?>
            <script>
                jQuery( document ).ready( function ( $ ) {
                    $( this ).trigger( 'mshop-setting-manager', [ 'mshop-setting-wrapper', '200', <?php echo json_encode( $this->get_setting_values( $this->id, $settings ) ); ?>, null, null ] );
                } );
            </script>

            <div id="mshop-setting-wrapper"></div>

            <style type="text/css">
                .ui.segment.inicis-keyfile-wrap {
                    overflow: hidden;
                    margin-right: 20px;
                    border-radius: 0;
                }

                .inicis-keyfile-wrap #inicis-keyfile-upload {
                    float: left;
                    overflow: hidden;
                    font-size: .9em;
                }

                .inicis-keyfile-wrap #inicis-keyfile-upload input:last-child {
                    background-color: #21ba45;
                    font-weight: 700;
                    transition: background .1s ease;
                    border: none;
                    color: #fff;
                    height: 2.4em;
                    padding: 0 10px;
                }

                .inicis-keyfile-wrap .submit {
                    float: left;
                    overflow: hidden;
                    padding: 0;
                    margin: 0;
                    overflow: hidden;
                }
            </style>

            <div class="ui segment dimmable inicis-keyfile-wrap">
                <p style="font-size: 12px; color: #2185d0; margin-bottom: 0px;">이니시스로 부터 전달 받은 상점 키파일을 등록 해 주세요</p>
                <div id="inicis-keyfile-upload">
                    상점 키파일 업로드(.zip) : <input id="upload_keyfile" type="file" size="36" name="upload_keyfile">
                    <input type="submit" name="submit" value="업로드">
                </div>
                <p style="font-size: 12px; color: #2185d0; margin-bottom: 0px;clear: both;">[중요] 상점 키파일은 일반결제 - COD 또는 MOD, 에스크로 - ESCOD 또는 ESMOD 로 시작해야 합니다.</p>
            </div>
			<?php

		}
		public function keyfile_upload_process() {
			if ( ! empty( $_FILES['upload_keyfile'] ) && isset( $_FILES['upload_keyfile'] ) ) {
				$options = get_option( 'pafw_mshop_inicis' );

				if ( ! empty( $options ) ) {
					$library_path = pafw_get( $options, 'libfolder' );
				}

				if ( empty( $library_path ) ) {
					$library_path = WP_CONTENT_DIR . '/inicis';
				}

				if ( ! file_exists( $library_path . '/upload' ) ) {
					$old = umask( 0 );
					mkdir( $library_path . '/upload', 0777, true );
					umask( $old );
				}

				if ( $_FILES['upload_keyfile']['size'] > 4086 ) {
					return false;
				}

				if ( ! class_exists( 'ZipArchive' ) ) {
					return false;
				}

				$zip = new ZipArchive();
				if ( isset( $_FILES['upload_keyfile']['tmp_name'] ) && ! empty( $_FILES['upload_keyfile']['tmp_name'] ) ) {
					if ( $zip->open( $_FILES['upload_keyfile']['tmp_name'] ) == true ) {
						for ( $i = 0; $i < $zip->numFiles; $i ++ ) {
							$filename = $zip->getNameIndex( $i );
							if ( ! in_array( $filename, array ( 'readme.txt', 'keypass.enc', 'mpriv.pem', 'mcert.pem' ) ) ) {
								return false;
							}
						}
					}

					$movefile = move_uploaded_file( $_FILES['upload_keyfile']['tmp_name'], $library_path . '/upload/' . $_FILES['upload_keyfile']['name'] );
					if ( $movefile ) {
						WP_Filesystem();
						$filepath  = pathinfo( $library_path . '/upload/' . $_FILES['upload_keyfile']['name'] );
						$unzipfile = unzip_file( $library_path . '/upload/' . $_FILES['upload_keyfile']['name'], $library_path . '/key/' . $filepath['filename'] );

						$this->init_form_fields();

						if ( ! is_wp_error( $unzipfile ) ) {
							if ( ! $unzipfile ) {
								return false;
							}

							return true;
						}
					} else {
						return false;
					}
				}
			}
		}

		protected function get_key() {
			return pafw_get( $_REQUEST, 'merchant_id' );
		}

		protected function valid_keys() {
			return array (
				array (
					'length' => 10,
					'value'  => 'SU5JcGF5VGVzdA=='
				),
				array (
					'length' => 5,
					'value'  => 'Y29kZW0='
				),
				array (
					'length' => 3,
					'value'  => 'Q09E'
				),
				array (
					'length' => 3,
					'value'  => 'TU9E'
				)
			);
		}

		protected function invalid_key_message() {
			return __( '유효하지 않은 상점 아이디 입니다. 상점 아이디는 "COD" 또는 "MOD"로 시작되어야 합니다.', 'pgall-for-woocommerce' );
		}
	}
}