import styled from 'styled-components';
import { DARK_BLUE, WHITE, MIDDLE_GREY } from '../../../shared/colors';

export const FieldLabel = styled.p`
  color: ${DARK_BLUE};
  font-weight: 600;
  margin-top: 0.7rem;
  margin-bottom: 0.5rem;
`;

export const HeaderWrapper = styled.div`
  position: sticky;
  z-index: 3;
  top: 0;
  display: flex;
  justify-content: space-between;
  background-color: ${WHITE};
  border-bottom-width: 1px;
  border-bottom-style: solid;
  border-bottom-color: ${MIDDLE_GREY};
`;

export const ProgressWrapper = styled.div`
  display: flex;
  flex-direction: column;
  align-items: center;
  position: absolute;
  bottom: 1px;
  right: 1.2rem;
`;

export const ListWithSearchWrapper = styled.div`
  padding: 0 1rem;
  padding-top: 0.5rem;
`;

const styles = () => ({
  button: {
    margin: '1.2rem',
    width: '20rem',
  },
  input: {
    display: 'none',
  },
  title: {
    fontWeight: 800,
    color: DARK_BLUE,
    margin: '1rem',
  },
  root: {
    height: '94.5vh',
    overflow: 'auto',
    display: 'block',
    backgroundColor: WHITE,
  },
  rightPanel: {
    boxShadow: 'none',
    maxWidth: '100%',
  },
  label: {
    color: DARK_BLUE,
    fontStyle: 'bold',
  },
  paper: {
    margin: 10,
    display: 'flex',
    flexDirection: 'column',
    boxShadow: 'none',
    width: '50%',
  },
  link: {
    color: WHITE,
    textDecoration: 'none',
  },
  spinnerCaption: {
    marginLeft: 5,
  },
});

export default styles;
