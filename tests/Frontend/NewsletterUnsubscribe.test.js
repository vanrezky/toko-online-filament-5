import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import NewsletterUnsubscribe from '../../resources/js/frontend/pages/Newsletter/Unsubscribe.vue';

// Stub that renders slots
const TemplateWrapperStub = {
    template: '<div><slot /></div>',
};

const LinkStub = {
    props: ['href'],
    template: '<a :href="href"><slot /></a>',
};

describe('Newsletter Unsubscribe Page', () => {
    const mountPage = (props) => mount(NewsletterUnsubscribe, {
        props,
        global: {
            stubs: {
                Link: LinkStub,
                TemplateWrapper: TemplateWrapperStub,
            },
        },
    });

    it('renders unsubscribed status correctly', () => {
        const wrapper = mountPage({
            status: 'unsubscribed',
            email: 'test@example.com',
        });

        expect(wrapper.text()).toContain('Successfully Unsubscribed');
        expect(wrapper.text()).toContain('test@example.com');
    });

    it('renders already unsubscribed status correctly', () => {
        const wrapper = mountPage({
            status: 'already_unsubscribed',
            email: 'test@example.com',
        });

        expect(wrapper.text()).toContain('Already Unsubscribed');
        expect(wrapper.text()).toContain('test@example.com');
    });

    it('shows back to home button', () => {
        const wrapper = mountPage({
            status: 'unsubscribed',
            email: 'test@example.com',
        });

        expect(wrapper.text()).toContain('Back to Home');
    });
});