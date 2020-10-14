// @flow
import React, { memo, PureComponent } from 'react';
import { CopyToClipboard as Clipboard } from 'react-copy-to-clipboard';
import { withStyles } from '@material-ui/core/styles';
import { IconButton } from '@material-ui/core';
import {
  FileCopyOutlined as CopyIcon,
  Done as CopiedIcon,
} from '@material-ui/icons';

import { COPY_TIMEOUT } from './config';

import type { ICopyToClipboardProps, ICopyToClipboardState } from './types';

import styles from './styles';

class CopyToClipboard extends PureComponent<ICopyToClipboardProps, ICopyToClipboardState> {
  state: ICopyToClipboardState = {
    isCopied: false,
  };

  handleCopy = (): void => {
    this.toggleCopy(true);

    setTimeout(() => {
      this.toggleCopy(false);
    }, COPY_TIMEOUT);
  }

  toggleCopy = (state: boolean): void => {
    this.setState({ isCopied: state });
  }

  render() {
    const { isCopied } = this.state;
    const { text, classes, medium = false } = this.props;

    return (
      <Clipboard
        text={text}
        onCopy={this.handleCopy}
      >
        <IconButton
          size="small"
          title={isCopied ? 'Copied' : 'Copy'}
          classes={{ root: classes[medium ? 'iconMediumButton' : 'iconButton'] }}
        >
          {isCopied ? <CopiedIcon /> : <CopyIcon />}
        </IconButton>
      </Clipboard>
    );
  }
}

export default withStyles(styles)(memo(CopyToClipboard));
