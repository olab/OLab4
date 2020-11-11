import styled from 'styled-components';

import { DARK_BLUE } from '../../shared/colors';

export const FieldLabel = styled.label`
  position: relative;
  color: ${DARK_BLUE};
  font-weight: 600;
  margin-top: 0.7rem;
`;

export const ListWithSearchWrapper = styled.div`
  padding: 0 1rem;
  padding-top: 0.5rem;
`;

export const SwitchWrapper = styled.article`
  display: flex;
  justify-content: space-between;
`;

export const styles = () => ({
  label: {
    color: DARK_BLUE,
    fontStyle: 'bold',
  },
  form: {
    // Fix IE 11 issue.
    width: '100%',
    marginTop: 1,
  },
  submit: {
    marginTop: '1rem',
    float: 'right',
    bottom: 0,
    width: 160,
  },
  textField: {
    marginTop: '0.5rem',
  },
  chip: {
    display: 'flex',
    justifyContent: 'space-between',
    position: 'absolute',
    width: '99%',
    bottom: 3,
    left: 3,
  },
  toggleButton: {
    borderRadius: 3,
    borderColor: 'rgb(0,0,0.87)',
    color: 'black',
    height: '25px',
  },
});

export default styles;
