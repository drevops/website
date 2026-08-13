// phpcs:ignoreFile
import Component from './image-list.twig';

const meta = {
  title: 'Organisms/Image list',
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
    images: {
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

export const ImageList = {
  parameters: {
    layout: 'fullscreen',
  },
  args: {
    theme: 'light',
    title: 'Who is in the network',
    content: '<p>Organisations taking part, shown in the order they joined.</p>',
    images: [
      placeholder('One'),
      placeholder('Two'),
      placeholder('Three'),
      placeholder('Four'),
      placeholder('Five'),
      placeholder('Six'),
      placeholder('Seven'),
      placeholder('Eight'),
    ],
    vertical_spacing: 'both',
    with_background: true,
    modifier_class: '',
    attributes: '',
  },
};
