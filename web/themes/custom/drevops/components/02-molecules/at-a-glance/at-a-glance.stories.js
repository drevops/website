// phpcs:ignoreFile
import Component from './at-a-glance.twig';

const meta = {
  title: 'Molecules/At a glance',
  component: Component,
  argTypes: {
    theme: {
      control: { type: 'radio' },
      options: ['light', 'dark'],
    },
    title: {
      control: { type: 'text' },
    },
    rows: {
      control: { type: 'object' },
    },
    vertical_spacing: {
      control: { type: 'radio' },
      options: ['none', 'top', 'bottom', 'both'],
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

export const AtAGlance = {
  parameters: {
    layout: 'fullscreen',
  },
  args: {
    theme: 'light',
    title: 'At a glance',
    rows: [
      { label: 'Client', values: [{ text: 'Department of Example' }] },
      { label: 'Role', values: [{ text: 'Technical lead' }, { text: 'Architect' }] },
      { label: 'Year', values: [{ text: '2025' }] },
      { label: 'Status', values: [{ text: 'Completed' }] },
      { label: 'Live site', values: [{ text: 'www.example.com', url: 'https://www.example.com', is_external: true }] },
      { label: 'Sector', values: [{ text: 'Federal government', url: '/taxonomy/term/1' }] },
      { label: 'Technologies', values: [{ text: 'Drupal', url: '/taxonomy/term/2' }, { text: 'Docker', url: '/taxonomy/term/3' }] },
    ],
    vertical_spacing: 'both',
    modifier_class: '',
    attributes: '',
  },
};

export const WithMissingFacts = {
  parameters: {
    layout: 'fullscreen',
  },
  args: {
    ...AtAGlance.args,
    rows: [
      { label: 'Role', values: [{ text: 'Technical lead' }] },
      { label: 'Year', values: [{ text: '2019' }] },
      { label: 'Status', values: [{ text: 'Decommissioned' }] },
    ],
  },
};
