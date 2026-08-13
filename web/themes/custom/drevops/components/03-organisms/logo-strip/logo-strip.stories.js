// phpcs:ignoreFile
import Component from './logo-strip.twig';

const meta = {
  title: 'Organisms/Logo strip',
  component: Component,
  argTypes: {
    theme: {
      control: { type: 'radio' },
      options: ['light', 'dark'],
    },
    title: {
      control: { type: 'text' },
    },
    content: {
      control: { type: 'text' },
    },
    logos: {
      control: { type: 'object' },
    },
    vertical_spacing: {
      control: { type: 'radio' },
      options: ['top', 'bottom', 'both'],
    },
    with_background: {
      control: { type: 'boolean' },
    },
    modifier_class: {
      control: { type: 'text' },
    },
    attributes: {
      control: { type: 'text' },
    },
  },
};

export default meta;

const placeholder = (label) => ({
  url: `https://placehold.co/240x80/eeeeee/333333/png?text=${encodeURIComponent(label)}`,
  alt: label,
});

export const LogoStrip = {
  parameters: {
    layout: 'fullscreen',
  },
  args: {
    theme: 'light',
    title: 'Open source we build and maintain',
    content: '<p>Tools we wrote for our own delivery and released for everyone else to use.</p>',
    logos: [
      placeholder('Vortex'),
      placeholder('CivicTheme'),
      placeholder('Publica'),
      placeholder('behat-steps'),
      placeholder('git-artifact'),
      placeholder('ci-runner'),
      placeholder('migratr'),
      placeholder('site-check'),
    ],
    vertical_spacing: 'both',
    with_background: true,
    modifier_class: '',
    attributes: '',
  },
};
