/**
 * Jlm SRL
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.ider.com/IDER-LICENSE-COMMUNITY.txt
 *
 ********************************************************************
 * @package    Jlmsrl_Iderlogin
 * @copyright  Copyright (c) 2016 - 2018 Jlm SRL (http://www.jlm.srl)
 * @license    https://www.ider.com/IDER-LICENSE-COMMUNITY.txt
 */

// Closest polyfill
if (!Element.prototype.matches) {
    Element.prototype.matches = Element.prototype.msMatchesSelector || Element.prototype.webkitMatchesSelector;
}

if (!Element.prototype.closest){
    Element.prototype.closest = function (s) {
        var el = this;
        if (!document.documentElement.contains(el)) return null;
        do {
            if (el.matches(s)) {
                return el;
            }
            el = el.parentElement || el.parentNode;
        } while (el !== null && el.nodeType === 1);
        return null;
    };
}

// Before polyfill
(function (arr) {
    arr.forEach(function (item) {
        if (item.hasOwnProperty('before')) {
            return;
        }
        Object.defineProperty(item, 'before', {
            configurable: true,
            enumerable: true,
            writable: true,
            value: function before() {
                var argArr = Array.prototype.slice.call(arguments),
                    docFrag = document.createDocumentFragment();

                argArr.forEach(function (argItem) {
                    var isNode = argItem instanceof Node;
                    docFrag.appendChild(isNode ? argItem : document.createTextNode(String(argItem)));
                });

                this.parentNode.insertBefore(docFrag, this);
            }
        });
    });
})([Element.prototype, CharacterData.prototype, DocumentType.prototype]);

// After the document loads.
document.addEventListener("DOMContentLoaded", function () {

    var iderLandingPagesInput = document.getElementById('IDER_LOGIN_CAMPAIGNS_LANDING_PAGES'),
        iderLandingPagesContainer = iderLandingPagesInput.closest('.form-group'),
        iderAdvancedSettings = document.createElement('div');

    // Configure the advanced toggle
    iderAdvancedSettings.setAttribute('id', 'IDER_ADVANCED_SETTINGS');
    iderAdvancedSettings.setAttribute('class', 'form-group');
    iderAdvancedSettings.innerHTML = '<div class="col-lg-4 col-lg-offset-3" style="margin-top: 10px;"><h2 style="margin-bottom: 0; margin-top: 0; font-size: 18px;">' + document.getElementById('IDER_LOGIN_STRING_ADVANCED_OPTIONS').innerHTML + '</h2><hr style="margin-bottom: -3px; margin-top: 6px;"></div></div>';

    // Add the toggle element
    iderLandingPagesContainer.before(iderAdvancedSettings);

});
