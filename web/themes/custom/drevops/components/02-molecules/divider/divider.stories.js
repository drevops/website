import Component from './divider.twig';

const meta = {
  title: 'Molecules/Divider',
  component: Component,
  argTypes: {
    theme: {
      control: { type: 'radio' },
      options: ['light', 'dark'],
    },
    image: {
      control: { type: 'object' },
    },
    vertical_spacing: {
      control: { type: 'radio' },
      options: ['none', 'top', 'bottom', 'both'],
    },
    alignment: {
      control: { type: 'radio' },
      options: ['left', 'center', 'right'],
    },
    size: {
      control: { type: 'radio' },
      options: ['none', 'large', 'regular', 'small'],
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

export const Divider = {
  parameters: {
    layout: 'centered',
  },
  args: {
    theme: 'light',
    image: {
      url: './demo/images/demo6.jpg',
      alt: 'Image alt text',
    },
    vertical_spacing: {
      control: { type: 'radio' },
      options: ['none', 'top', 'bottom', 'both'],
    },
    alignment: {
      control: { type: 'radio' },
      options: ['left', 'center', 'right'],
    },
    size: {
      control: { type: 'radio' },
      options: ['none', 'large', 'regular', 'small'],
    },
    modifier_class: '',
    attributes: '',
  },
};
