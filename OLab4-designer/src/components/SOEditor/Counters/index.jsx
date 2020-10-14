// @flow
import React from 'react';
import { TextField, Chip } from '@material-ui/core';

import ScopedObjectService, { withSORedux } from '../index.service';

import OutlinedInput from '../../../shared/components/OutlinedInput';
import OutlinedSelect from '../../../shared/components/OutlinedSelect';
import EditorWrapper from '../../../shared/components/EditorWrapper';
import SearchModal from '../../../shared/components/SearchModal';

import type { IScopedObjectProps } from '../types';

import { VISIBILITY, COUNTER_STATUSES } from './config';
import { EDITORS_FIELDS } from '../config';
import { SCOPE_LEVELS, SCOPED_OBJECTS } from '../../config';

import { FieldLabel } from '../styles';

class Counters extends ScopedObjectService {
  constructor(props: IScopedObjectProps) {
    super(props, SCOPED_OBJECTS.COUNTER);
    this.state = {
      name: '',
      description: '',
      scopeLevel: SCOPE_LEVELS[0],
      visible: 0,
      status: 0,
      startValue: '',
      isShowModal: false,
      isFieldsDisabled: false,
    };
  }

  handleSelectChoose = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);

    let valuesList;

    switch (name) {
      case 'status':
        valuesList = COUNTER_STATUSES;
        break;
      case 'visible':
        valuesList = VISIBILITY;
        break;
      default: break;
    }

    const valueNumbered = valuesList.findIndex(item => item === value);

    this.setState({ [name]: valueNumbered });
  }

  render() {
    const {
      name,
      description,
      scopeLevel,
      startValue,
      status,
      visible,
      isShowModal,
      isFieldsDisabled,
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
          {EDITORS_FIELDS.STARTING_VALUE}
          <TextField
            multiline
            rows="1"
            name="startValue"
            placeholder={EDITORS_FIELDS.STARTING_VALUE}
            className={classes.textField}
            margin="normal"
            variant="outlined"
            value={startValue}
            onChange={this.handleInputChange}
            disabled={isFieldsDisabled}
            fullWidth
          />
        </FieldLabel>
        <FieldLabel>
          {EDITORS_FIELDS.SCOPED_OBJECT_STATUS}
        </FieldLabel>
        <OutlinedSelect
          name="status"
          value={COUNTER_STATUSES[status]}
          values={COUNTER_STATUSES}
          onChange={this.handleSelectChoose}
          disabled={isFieldsDisabled}
        />
        <FieldLabel>
          {EDITORS_FIELDS.VISIBLE}
        </FieldLabel>
        <OutlinedSelect
          name="visible"
          value={VISIBILITY[visible]}
          values={VISIBILITY}
          onChange={this.handleSelectChoose}
          disabled={isFieldsDisabled}
        />
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

export default withSORedux(Counters, SCOPED_OBJECTS.COUNTER);
