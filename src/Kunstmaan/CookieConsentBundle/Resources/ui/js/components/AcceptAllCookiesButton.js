import Component from './Component';

import { BUTTON_IDENTIFIER } from '../config/acceptAllCookies.config';
import {
    dispatch,
    SET_VISIBILITY_SCOPE_TO_NOTIFICATION,
} from '../state';

import cookies from '../services/cookies';

class AcceptAllCookiesButton extends Component {
    constructor() {
        super({
            identifier: BUTTON_IDENTIFIER,
            eventListeners: {
                click: 'handleAcceptAllCookies',
            },
        });
    }

    handleAcceptAllCookies(e) {
        e.preventDefault();
        // Get href attribute for url to call.
        if (this.vdom.hasAttribute('data-href')) {
            const toggleAllCookiesUrl = this.vdom.getAttribute('data-href');
            cookies.toggleAll(toggleAllCookiesUrl);
            dispatch(SET_VISIBILITY_SCOPE_TO_NOTIFICATION);
        } else {
            throw new Error('Expected a data-href attribute to be present.');
        }
    }
}

export default AcceptAllCookiesButton;
