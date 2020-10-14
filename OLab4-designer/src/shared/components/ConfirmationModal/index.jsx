// @flow
import React, { PureComponent } from 'react';
import {
  Modal,
  Button,
  Typography,
} from '@material-ui/core';
import { Close as CloseIcon } from '@material-ui/icons';

import type { IConfirmationModalProps } from './types';

import {
  ModalContainer,
  ModalWrapper,
  ModalFooter,
  CrossButtonWrapper,
} from './styles';

class ConfirmationModal extends PureComponent<IConfirmationModalProps> {
  static defaultProps = {
    cancelBttnLabel: 'Cancel',
    saveBttnLabel: 'Save',
    showFooterButtons: false,
  };

  handleSubmit = (e: Event): void => {
    if (e.preventDefault) {
      e.preventDefault();
    }

    const { onSave } = this.props;
    if (onSave) {
      onSave();
    }
  }

  render() {
    const {
      label,
      text,
      onClose,
      onSave,
      cancelBttnLabel,
      saveBttnLabel,
      showFooterButtons,
      children,
    } = this.props;

    return (
      <Modal
        aria-labelledby="confirmation-modal-title"
        aria-describedby="confirmation-modal-description"
        onClose={onClose}
        open
      >
        <ModalContainer>
          <ModalWrapper>
            <CrossButtonWrapper onClick={onClose}>
              <CloseIcon />
            </CrossButtonWrapper>
            <Typography variant="h5">
              {label}
            </Typography>
            <Typography variant="subtitle1">
              {text}
            </Typography>
            {children && (
              <form action="" onSubmit={this.handleSubmit}>
                {children}
              </form>
            )}
            {showFooterButtons && (
              <ModalFooter>
                <Button
                  variant="outlined"
                  color="secondary"
                  onClick={onClose}
                >
                  {cancelBttnLabel}
                </Button>
                <Button
                  variant="outlined"
                  color="primary"
                  onClick={onSave}
                >
                  {saveBttnLabel}
                </Button>
              </ModalFooter>
            )}
          </ModalWrapper>
        </ModalContainer>
      </Modal>
    );
  }
}

export default ConfirmationModal;
