// phpcs:ignoreFile
import Component from './steps.twig';

const meta = {
  title: 'Organisms/Steps',
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
    items: {
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

export const Steps = {
  parameters: {
    layout: 'fullscreen',
  },
  args: {
    theme: 'light',
    title: 'Six steps, and what you get at each one.',
    content: '<p>Every engagement follows the same path. Each step builds on the one before it.</p>',
    items: [
      {
        title: 'The first conversation',
        body: 'We listen and map what you actually need, which is often a little different from the initial brief.',
        receive: 'A written summary of your goals and scope, confirmed with you before any number exists.',
      },
      {
        title: 'A proper assessment',
        body: 'We review your real platform, the code, the integrations, and the risks, not just the description of it.',
        receive: 'A clear, honest report you can act on and share.',
      },
      {
        title: 'A transparent quotation',
        body: 'We price the work from a standard rate card, line by line, adding a buffer only where there are genuine unknowns.',
        receive: 'Two complete options, hand-built and AI-assisted, with the hours shown and nothing hidden.',
      },
    ],
    vertical_spacing: 'both',
    with_background: false,
    modifier_class: '',
    attributes: '',
  },
};
