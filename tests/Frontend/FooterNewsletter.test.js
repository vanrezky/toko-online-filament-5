import { describe, it, expect } from 'vitest';
import { mount } from '@vue/test-utils';
import Footer from '../../resources/js/frontend/components/Templates/Default/Footer.vue';

describe('Footer Newsletter Form', () => {
    it('renders newsletter form', () => {
        const wrapper = mount(Footer, {
            global: {
                stubs: {
                    PromotionBanner: true,
                },
            },
        });

        expect(wrapper.find('input[type="email"]').exists()).toBe(true);
        expect(wrapper.find('button[type="submit"]').exists()).toBe(true);
    });

    it('has email input with correct placeholder', () => {
        const wrapper = mount(Footer, {
            global: {
                stubs: {
                    PromotionBanner: true,
                },
            },
        });

        const input = wrapper.find('input[type="email"]');
        expect(input.attributes('placeholder')).toBe('Enter your email');
    });

    it('can type email into input', async () => {
        const wrapper = mount(Footer, {
            global: {
                stubs: {
                    PromotionBanner: true,
                },
            },
        });

        const input = wrapper.find('input[type="email"]');
        await input.setValue('test@example.com');

        expect(input.element.value).toBe('test@example.com');
    });
});