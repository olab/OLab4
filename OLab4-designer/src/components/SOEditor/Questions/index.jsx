// @flow
import React from 'react';
import { Chip } from '@material-ui/core';

// import CircularSpinnerWithText from '../../../shared/components/CircularSpinnerWithText';
import { FieldLabel } from '../styles';
import { getKeyByValue } from './utils';
import {
  DEFAULT_WIDTH,
  DEFAULT_HEIGHT,
  LAYOUT_TYPES,
} from './config';
import {
  QUESTION_TYPES,
  TEXTENTRY_QUESTION_TYPES,
  CHOICE_QUESTION_TYPES,
  EDITORS_FIELDS,
} from '../config';
import { SCOPE_LEVELS, SCOPED_OBJECTS } from '../../config';
import EditorWrapper from '../../../shared/components/EditorWrapper';
import OutlinedInput from '../../../shared/components/OutlinedInput';
import OutlinedSelect from '../../../shared/components/OutlinedSelect';
import ScopedObjectService, { withSORedux } from '../index.service';
import SearchModal from '../../../shared/components/SearchModal';
import ChoiceQuestionLayout from './ChoiceQuestionLayout';
import TextQuestionLayout from './TextQuestionLayout';
import SliderQuestionLayout from './SliderQuestionLayout';
import type { IScopedObjectProps } from '../types';

class Questions extends ScopedObjectService {
  constructor(props: IScopedObjectProps) {
    super(props, SCOPED_OBJECTS.QUESTION.name);
    this.state = {
      description: '',
      feedback: '',
      height: DEFAULT_HEIGHT.MIN,
      id: 0,
      isFieldsDisabled: false,
      isShowAnswer: false,
      isShowModal: false,
      isShowSubmit: false,
      layoutType: 0,
      name: '',
      placeholder: '',
      questionId: 0,
      questionType: Number(Object.keys(QUESTION_TYPES)[0]),
      responses: [],
      scopeLevel: SCOPE_LEVELS[0],
      settings: '{}',
      stem: '',
      width: DEFAULT_WIDTH.MIN,
    };
  }

  onSettingsChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);

    const settingsObject = JSON.parse(this.state.settings);
    settingsObject[name] = value;
    const settingsName = 'settings';
    const settings = JSON.stringify(settingsObject);
    this.setState({ [settingsName]: settings });
  };

  onLayoutTypeChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const index = LAYOUT_TYPES.findIndex(type => type === value);
    this.setState({ [name]: index });
  };

  onQuestionTypeChange = (e: Event): void => {
    const { value, name } = (e.target: window.HTMLInputElement);
    const key = Number(getKeyByValue(QUESTION_TYPES, value));
    this.setState({ [name]: key });
  };

  render() {
    const {
      id,
      isFieldsDisabled,
      isShowModal,
      questionType,
      scopeLevel,
    } = this.state;

    const { classes, scopeLevels } = this.props;
    const { iconEven: IconEven, iconOdd: IconOdd } = this.icons;

    const isChoiceQuestion = (questionType in CHOICE_QUESTION_TYPES);
    const isSliderQuestion = QUESTION_TYPES[questionType] === QUESTION_TYPES[5];
    const isTextQuestion = (questionType in TEXTENTRY_QUESTION_TYPES);

    const idInfo = ` (Id: ${id})`;

    return (
      <EditorWrapper
        isEditMode={this.isEditMode}
        isDisabled={isFieldsDisabled}
        scopedObject={this.scopedObjectType}
        onSubmit={this.handleSubmitScopedObject}
      >
        {(id !== 0) && (
          <small>{idInfo}</small>
        )}

        {(!this.isEditMode) && (questionType === 0) && (
          <>
            <FieldLabel>
              {EDITORS_FIELDS.QUESTION_TYPES}
            </FieldLabel>
            <OutlinedSelect
              name="questionType"
              value={QUESTION_TYPES[questionType]}
              values={Object.values(QUESTION_TYPES)}
              onChange={this.onQuestionTypeChange}
              disabled={isFieldsDisabled}
            />
          </>
        )}

        {(isChoiceQuestion) && (
          <ChoiceQuestionLayout
            onInputChange={this.onInputChange}
            onLayoutTypeChange={this.onLayoutTypeChange}
            onQuestionTypeChange={this.onQuestionTypeChange}
            onSwitchChange={this.onSliderOrSwitchChange}
            props={this.props}
            state={this.state}
          />
        )}

        {(isTextQuestion) && (
          <TextQuestionLayout
            onInputChange={this.onInputChange}
            onQuestionTypeChange={this.onQuestionTypeChange}
            onSelectChange={this.handleLayoutTypeChange}
            onSwitchChange={this.onSliderOrSwitchChange}
            props={this.props}
            state={this.state}
          />
        )}


        {(isSliderQuestion) && (
          <SliderQuestionLayout
            onInputChange={this.onInputChange}
            onQuestionTypeChange={this.onQuestionTypeChange}
            onSettingsChange={this.onSettingsChange}
            onSwitchChange={this.onSliderOrSwitchChange}
            props={this.props}
            state={this.state}
          />
        )}

        {(questionType !== 0) && (
          <>
            {!this.isEditMode && (
              <>
                <FieldLabel>
                  {EDITORS_FIELDS.SCOPE_LEVEL}
                </FieldLabel>
                <OutlinedSelect
                  name="scopeLevel"
                  value={scopeLevel}
                  values={SCOPE_LEVELS}
                  onChange={this.onInputChange}
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
          </>
        )}
      </EditorWrapper>
    );
  }
}

export default withSORedux(Questions, SCOPED_OBJECTS.QUESTION.name);
