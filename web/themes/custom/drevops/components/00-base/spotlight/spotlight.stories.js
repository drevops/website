import Component from './spotlight.twig';

const meta = {
  title: 'Base/Spotlight',
  component: Component,
  argTypes: {
    items_count: {
      control: {
        type: 'range',
        min: 0,
        max: 20,
        step: 1,
      },
    },
    template_column_count: {
      control: {
        type: 'range',
        min: 0,
        max: 12,
        step: 1,
      },
    },
    fill_width: {
      control: { type: 'boolean' },
    },
    row_element: {
      control: { type: 'text' },
    },
    row_class: {
      control: { type: 'text' },
    },
    row_attributes: {
      control: { type: 'text' },
    },
    column_element: {
      control: { type: 'text' },
    },
    column_class: {
      control: { type: 'text' },
    },
    column_attributes: {
      control: { type: 'text' },
    },
    use_container: {
      control: { type: 'boolean' },
    },
    is_fluid: {
      control: { type: 'boolean' },
    },
    attributes: {
      control: { type: 'text' },
    },
    modifier_class: {
      control: { type: 'text' },
    },
  },
};

export default meta;

export const Spotlight = {
  args: {
    items_count: 3,
    template_column_count: 3,
    fill_width: false,
    row_element: 'div',
    row_class: '',
    row_attributes: '',
    column_element: 'div',
    column_class: '',
    column_attributes: '',
    use_container: true,
    is_fluid: false,
    attributes: '',
    modifier_class: '',
  },
  parameters: {
    layout: 'padded',
  },
  render: (args) => {
    const { items_count: itemsCount, ...componentArgs } = args;

    const items = Array.from({ length: itemsCount }, (_, i) => {
      const brCount = Math.floor(Math.random() * 10) + 1; // Random int from 1 to 10
      return `<div class="story-placeholder" contenteditable="true">
            Item ${i + 1}${'<br>'.repeat(brCount)}
          </div>`;
    });

    return Component({
      ...componentArgs,
      items,
    });
  },
};
