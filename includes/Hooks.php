<?php
/**
 * Add a link to user's personal uploads to personal tools menu.
 *
 * https://www.mediawiki.org/wiki/Extension:UploadsLink
 *
 * @file
 * @license MIT
 */

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

namespace MediaWiki\Extension\UploadsLink;

use MediaWiki\Hook\SidebarBeforeOutputHook;
use MediaWiki\Hook\SkinTemplateNavigation__UniversalHook;
use MediaWiki\Skin\Skin;
use MediaWiki\SpecialPage\SpecialPage;
use MediaWiki\Title\Title;

class Hooks implements
	SkinTemplateNavigation__UniversalHook,
	SidebarBeforeOutputHook
{
	/**
	 * Return a Title for the uploads page of the user provided.
	 *
	 * @param string $username
	 * @return Title
	 */
	private static function getUploadsTitle( $username ) {
		return SpecialPage::getTitleFor( 'Listfiles', $username );
	}

	/**
	 * Return a link descriptor for the page where the current user's uploads listing is,
	 * relative to current title and in current language.
	 *
	 * @param Skin $skin For context
	 * @return array Link descriptor in a format accepted by PersonalUrls hook
	 */
	private static function makePersonalUploadsLink( Skin $skin ) {
		$currentTitle = $skin->getTitle();

		$username = $skin->getUser()->getName();
		$title = self::getUploadsTitle( $username );

		$href = $title->getLocalURL( [ 'ilshowall' => '1' ] );

		return [
			'id' => 'pt-uploads',
			'icon' => 'imageGallery',
			'text' => $skin->msg( 'uploadslink-portlet-label' )->text(),
			'href' => $href,
			'active' => $title->equals( $currentTitle ),
		];
	}

	/**
	 * PersonalUrls hook handler.
	 *
	 * Possibly add a link to the page where the current user's uploads listing
	 * is to personal tools menu.
	 *
	 * @param Skin $skin
	 * @param array &$links
	 */
	public function onSkinTemplateNavigation__Universal( $skin, &$links ): void {
		global $wgUploadsLinkDisableAnon, $wgUploadsLinkEnablePersonalLink;

		if ( !$wgUploadsLinkEnablePersonalLink
			|| ( $wgUploadsLinkDisableAnon && !$skin->getUser()->isNamed() ) ) {
				return;
		}

		$link = self::makePersonalUploadsLink( $skin );

		$newPersonalUrls = [];
		$done = false;
		$personalUrls = $links['user-menu'] ?? [];

		// Insert our link before the link to user contribs.
		// If the link to contribs is missing, insert at the end.
		foreach ( $personalUrls as $key => $value ) {
			if ( $key === 'mycontris' ) {
				$newPersonalUrls['uploads'] = $link;
				$done = true;
			}
			$newPersonalUrls[$key] = $value;
		}
		if ( !$done ) {
			$newPersonalUrls['uploads'] = $link;
		}

		$links['user-menu'] = $newPersonalUrls;
	}

	/**
	 * Return a link descriptor for the page where the relevant user's uploads listing is,
	 * relative to current title and in current language.
	 *
	 * @param Skin $skin For context
	 * @return array|null Link descriptor or null if link cannot be created
	 */
	private static function makeRelevantUserUploadsLink( Skin $skin ) {
		$user = $skin->getRelevantUser();
		if ( !$user ) {
			return null;
		}

		$rootUser = $user->getName();
		$title = self::getUploadsTitle( $rootUser );

		$currentTitle = $skin->getTitle();

		$href = $title->getLocalURL( [ 'ilshowall' => '1' ] );

		// Although the user name might not be used in the message directly,
		// it is used to distinguish between feminine and masculine form
		// in some languages.
		return [
			'id' => 'tb-uploads',
			'text' => $skin->msg( 'uploadslink-toobox-label' )->params( $rootUser )->text(),
			'href' => $href,
			'active' => $title->equals( $currentTitle ),
			'tooltip-params' => [ $rootUser ],
		];
	}

	/**
	 * SidebarBeforeOutput hook handler.
	 *
	 * Possibly add a link to the page where the relevant user's uploads listing
	 * is to toolbox menu.
	 *
	 * @param Skin $skin
	 * @param array &$sidebar
	 * @return void
	 */
	public function onSidebarBeforeOutput( $skin, &$sidebar ): void {
		global $wgUploadsLinkEnableRelevantUserLink;

		if ( !$wgUploadsLinkEnableRelevantUserLink ) {
			return;
		}

		$link = self::makeRelevantUserUploadsLink( $skin );
		if ( !$link ) {
			return;
		}

		$newToolbox = [];
		$done = false;

		// Insert our link before the link to user contribs.
		foreach ( $sidebar['TOOLBOX'] as $key => $value ) {
			if ( $key === 'contributions' ) {
				$newToolbox['uploads'] = $link;
				$done = true;
			}
			$newToolbox[$key] = $value;
		}

		$sidebar['TOOLBOX'] = $newToolbox;

		if ( !$done ) {
			// If the link was not inserted, just insert it at the end.
			$sidebar['TOOLBOX']['uploads'] = $link;
		}
	}
}
