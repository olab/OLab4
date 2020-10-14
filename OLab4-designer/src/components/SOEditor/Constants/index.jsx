// @flow
import React from 'react';
import { TextField, Chip } from '@material-ui/core';

import ScopedObjectService, { withSORedux } from '../index.service';

import OutlinedInput from '../../../shared/components/OutlinedInput';
import OutlinedSelect from '../../../shared/components/OutlinedSelect';
import EditorWrapper from '../../../shared/components/EditorWrapper';
import SearchModal from '../../../shared/components/SearchModal';

import type { IScopedObjectProps } from '../types';

import { EDITORS_FIELDS } from '../config';
import { SCOPE_LEVELS, SCOPED_OBJECTS } from '../../config';

import { FieldLabel } from '../styles';

class Constant extends ScopedObjectService {
  constructor(props: IScopedObjectProps) {
    super(props, SCOPED_OBJECTS.CONSTANT);
    this.state = {
      name: '',
      description: '',
      value: '',
      scopeLevel: SCOPE_LEVELS[0],
      isShowModal: false,
      isFieldsDisabled: false,
    };
  }

  render() {
    const {
      name, description, value, scopeLevel, isShowModal, isFieldsDisabled,
    } = this.state;
    const { classes, scopeLevels } = this.props;
    const { iconEven: IconEven, iconOdd: IconOdd } = this.icons;

    return (
      <EditorWrapper
        isEditMode={this.isEditMode}
        isDisabled={isFieldsDisabled}
        scopedObject={this.scopedObjectType}
        onSubmit={this.handleSubmitScopedObject}
      >
        <FieldLabel>
          {EDITORS_FIELDS.NAME}
          <OutlinedInput
            name="name"
            placeholder={EDITORS_FIELDS.NAME}
            value={name}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        <FieldLabel>
          {EDITORS_FIELDS.DESCRIPTION}
          <TextField
            multiline
            rows="3"
            name="description"
            placeholder={EDITORS_FIELDS.DESCRIPTION}
            className={classes.textField}
            margin="normal"
            variant="outlined"
            value={description}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        <FieldLabel>
          {EDITORS_FIELDS.TEXT}
          <TextField
            multiline
            rows="6"
            name="value"
            placeholder={EDITORS_FIELDS.TEXT}
            className={classes.textField}
            margin="normal"
            variant="outlined"
            value={value}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        {!this.isEditMode && (
          <>
            <FieldLabel>
              {EDITORS_FIELDS.SCOPE_LEVEL}
            </FieldLabel>
            <OutlinedSelect
              name="scopeLevel"
              value={scopeLevel}
              values={SCOPE_LEVELS}
              onChange={this.handleInputChange}
              disabled={isFieldsDisabled}
            />
            <FieldLabel>
              {EDITORS_FIELDS.PARENT}
              <OutlinedInput
                name="parentId"
                placeholder={this.scopeLevelObj ? '' : EDITORS_FIELDS.PARENT}
                disabled={isFieldsDisabled}
                onFocus={this.showModal}
                setRef={this.setParentRef}
                readOnly
                fullWidth
              />
              {this.scopeLevelObj && (
                <Chip
                  className={classes.chip}
                  label={this.scopeLevelObj.name}
                  variant="outlined"
                  color="primary"
                  onDelete={this.handleParentRemove}
                  icon={<IconEven />}
                />
              )}
            </FieldLabel>
          </>
        )}

        {isShowModal && (
          <SearchModal
            label="Parent record"
            searchLabel="Search for parent record"
            items={scopeLevels[scopeLevel.toLowerCase()]}
            text={`Please choose appropriate parent from ${scopeLevel}:`}
            onClose={this.hideModal}
            onItemChoose={this.handleLevelObjChoose}
            isItemsFetching={scopeLevels.isFetching}
            iconEven={IconEven}
            iconOdd={IconOdd}
          />
        )}
      </EditorWrapper>
    );
  }
}

export default withSORedux(Constant, SCOPED_OBJECTS.CONSTANT);
