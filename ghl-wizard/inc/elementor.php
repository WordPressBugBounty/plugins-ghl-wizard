<?php
/**
 * Elementor page builder integration.
 * @author obiPlabon
 */
namespace Connector_Wizard\Integrations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Element_Base;
use Elementor\Controls_Manager;

class Elementor_Page_Builder {

	public static function init() {
		add_action( 'elementor/element/container/section_layout/after_section_end', [__CLASS__, 'register_controls'], 1 );
		add_action( 'elementor/element/column/section_advanced/after_section_end', [__CLASS__, 'register_controls'], 1 );
		add_action( 'elementor/element/section/section_advanced/after_section_end', [__CLASS__, 'register_controls'], 1 );
		add_action( 'elementor/element/common/_section_style/after_section_end', [__CLASS__, 'register_controls'], 1 );

		add_action( 'elementor/editor/after_enqueue_scripts', [__CLASS__, 'enqueue_scripts'] );
	}

	public static function location_tags_to_options(): array {
		$tags    = hlwpw_get_location_tags();
		$options = [];
		foreach ( $tags as $tag ) {
			$options[ esc_attr( $tag->name ) ] = esc_html( $tag->name );
		}
		return $options;
	}

	public static function register_controls( Element_Base $element ) {
		$element->start_controls_section(
			'_section_lcw',
			[
				'label' => self::get_icon() . esc_html__( 'LC Wizard', 'lc-wizard-pro' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'lcw_membership_any',
			[
				'label'       => esc_html__( 'Any Membership', 'lc-wizard-pro' ),
				'type'        => Controls_Manager::SWITCHER,
				'render_type' => 'none',
			]
		);

		$memberships = lcw_get_memberships();
		if ( ! empty( $memberships ) && is_array( $memberships ) ) {
			foreach ( $memberships as $membership ) {
				$membership_name = $membership['membership_name'];

				$element->add_control(
					'lcw_membership_' . esc_attr( $membership_name ),
					[
						'label'       => esc_html( $membership_name ),
						'type'        => Controls_Manager::SWITCHER,
						'render_type' => 'none',
					]
				);
			}
		}

		$element->add_control(
			'lcw_logged_in_users',
			[
				'label'       => esc_html__( 'Only Logged in Users', 'lc-wizard-pro' ),
				'type'        => Controls_Manager::SWITCHER,
				'render_type' => 'none',
				'separator'   => 'before',
			]
		);

		$element->add_control(
			'lcw_logged_out_users',
			[
				'label'       => esc_html__( 'Only Logged Out User', 'lc-wizard-pro' ),
				'type'        => Controls_Manager::SWITCHER,
				'render_type' => 'none',
				'separator'   => 'after',
			]
		);

		$element->add_control(
			'lcw_required_tags',
			[
				'label'       => esc_html__( 'Required Tags', 'lc-wizard-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => self::location_tags_to_options(),
				'render_type' => 'none',
			]
		);

		$element->add_control(
			'lcw_and_required_tags',
			[
				'label'       => esc_html__( 'AND Required Tags', 'lc-wizard-pro' ),
				'type'        => Controls_Manager::SELECT2,
				'label_block' => true,
				'multiple'    => true,
				'options'     => self::location_tags_to_options(),
				'render_type' => 'none',
			]
		);

		$element->add_control(
			'lcw_pro_alert',
			[
				'type' => Controls_Manager::RAW_HTML,
				'raw'  => self::get_teaser_template( [
					'title'    => esc_html__( 'Power Up Your Automation', 'elementor' ),
					'messages' => ['Unlock premium features and become the hero of automation!'],
					'link'     => 'https://betterwizard.com/lead-connector-wizard?utm_source=plugin&utm_medium=elementor-editor&utm_campaign=upgrade_notice',
				] ),
			]
		);

		$element->end_controls_section();
	}

	public static function enqueue_scripts() {
		$js = "
		jQuery(window).on('elementor/init', function () {
			function disableMemberships() {
				jQuery('[data-setting^=lcw_membership_]').each(function() {
					this.checked && this.click();
				});
			}

			function disableLoggedInOut() {
				jQuery('[data-setting=lcw_logged_in_users], [data-setting=lcw_logged_out_users]').each(function() {
					this.checked && this.click();
				});
			}
			
			elementor.channels.editor.on('change', function (controlView) {
				const controlsAlternateMap = {
					'lcw_logged_in_users' : 'lcw_logged_out_users',
					'lcw_logged_out_users': 'lcw_logged_in_users',
				};
				const currentControlName    = controlView.model.get('name');
				const container             = controlView.container;
				const currentControlValue   = container.settings.get(currentControlName);
				const alternateControlValue = container.settings.get(controlsAlternateMap[currentControlName]);

				if (currentControlName === 'lcw_logged_in_users' && currentControlValue === 'yes' && alternateControlValue === 'yes') {
					jQuery('[data-setting=lcw_logged_out_users]').click();
				}

				if (currentControlName === 'lcw_logged_out_users' && currentControlValue === 'yes' && alternateControlValue === 'yes') {
					jQuery('[data-setting=lcw_logged_in_users]').click();
				}

				if ((currentControlName === 'lcw_logged_in_users' || currentControlName === 'lcw_logged_out_users') && currentControlValue === 'yes') {
					disableMemberships();
				}

				if (currentControlName === 'lcw_membership_any' && currentControlValue === 'yes') {
					jQuery('[data-setting^=lcw_membership_]').filter(function() {
						return this.dataset.setting !== 'lcw_membership_any';
					}).each(function() {
						this.checked && this.click();
					});

					disableLoggedInOut();
				} else if (currentControlName !== 'lcw_membership_any' && currentControlName.includes('lcw_membership_') && currentControlValue === 'yes') {
					const amc = jQuery('[data-setting=lcw_membership_any]')[0];
					amc.checked && amc.click();

					disableLoggedInOut();
				}
			});
		});
		";
		wp_add_inline_script( 'elementor-editor', $js );
	}

	public static function get_icon() {
		return '<img style="width:22px;margin-right:5px;vertical-align:middle;margin-bottom:2px;" src="' . plugin_dir_url( HLWPW_PLUGIN_BASENAME ) . 'logo-star-icon.svg" alt="icon" />';
	}

	public static function get_teaser_template( $texts ) {
		ob_start();
		?>
		<div class="elementor-nerd-box">
			<img class="elementor-nerd-box-icon" src="<?php echo esc_url( plugin_dir_url( HLWPW_PLUGIN_BASENAME ) . 'go-pro.svg' ); ?>" loading="lazy" alt="<?php echo esc_attr__( 'Upgrade', 'lc-wizard-pro' ); ?>" />
			<div class="elementor-nerd-box-title"><?php echo esc_html( $texts['title'] ); ?></div>
			<?php foreach ( $texts['messages'] as $message ) { ?>
				<div class="elementor-nerd-box-message"><?php echo esc_html( $message ); ?></div>
			<?php }

			// Show the upgrade button.
			if ( $texts['link'] ) { ?>
				<a style="--e-a-btn-bg-accent:#ffbc03;--e-a-btn-bg-accent-hover:#dfa402;--e-a-btn-color-invert:#333" class="elementor-button go-pro" href="<?php echo esc_url( ( $texts['link'] ) ); ?>" target="_blank">
					<?php echo esc_html__( 'Unlock Now', 'lc-wizard-pro' ); ?>
				</a>
			<?php } ?>
		</div>
		<?php

		return ob_get_clean();
	}
}

Elementor_Page_Builder::init();
