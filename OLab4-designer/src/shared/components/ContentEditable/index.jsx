// @flow
import React, { Component } from 'react';
import { sanitize } from 'dompurify';

import type { ContentEditableProps as IProps } from './types';

import { TextField } from './styles';

class ContentEditable extends Component<IProps> {
  contentEditableRef: React.Ref<HTMLDivElement> = React.createRef();

  lastHtml: string = '';

  shouldComponentUpdate(nextProps: IProps): boolean {
    const { innerHTML: html } = this.contentEditableRef.current;

    return String(nextProps.html) !== html;
  }

  emitChange = (): void => {
    const { onChange } = this.props;
    const { innerHTML: html } = this.contentEditableRef.current;

    if (html !== this.lastHtml) {
      onChange(html);
      this.lastHtml = html;
    }
  }

  render() {
    const { html, onFocus } = this.props;

    return (
      <TextField
        ref={this.contentEditableRef}
        onInput={this.emitChange}
        onFocus={onFocus}
        dangerouslySetInnerHTML={{ __html: sanitize(html) }}
        contentEditable
      />
    );
  }
}

export default ContentEditable;
