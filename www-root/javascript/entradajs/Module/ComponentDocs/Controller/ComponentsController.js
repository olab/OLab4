/**
 * ComponentsController.js
 * @author Scott Gibson
 */

const ControllerAbstract = use('EntradaJS/Controller/ControllerAbstract');

const preloadedComponents = {
    ComponentsPage: use('./../Component/Pages/Components.vue')
};

module.exports = class ComponentsController extends ControllerAbstract
{
    indexAction() {
        return preloadedComponents.ComponentsPage;
    }

    advancedTableAction() {
        return this.respond('Pages/AdvancedTableDoc.vue');
    }

    alertAction() {
        return this.respond('Pages/AlertDoc.vue');
    }

    badgeAction() {
        return this.respond('Pages/BadgeDoc.vue');
    }

    buttonAction() {
        return this.respond('Pages/ButtonDoc.vue');
    }

    cardAction() {
        return this.respond('Pages/CardDoc.vue');
    }

    checkboxAction() {
        return this.respond('Pages/CheckboxDoc.vue');
    }

    layoutAction() {
        return this.respond('Pages/LayoutDoc.vue');
    }

    listItemAction() {
        return this.respond('Pages/ListItemDoc.vue');
    }

    mediaAction() {
        return this.respond('Pages/MediaDoc.vue');
    }

    modalAction() {
        return this.respond('Pages/ModalDoc.vue');
    }

    multipleLineInputAction() {
        return this.respond('Pages/MultipleLineInputDoc.vue');
    }

    popoverAction() {
        return this.respond('Pages/PopoverDoc.vue');
    }

    radioAction() {
        return this.respond('Pages/RadioDoc.vue');
    }

    selectInputAction() {
        return this.respond('Pages/SelectInputDoc.vue');
    }

    simpleTableAction() {
        return this.respond('Pages/SimpleTableDoc.vue');
    }

    singleLineInputAction() {
        return this.respond('Pages/SingleLineInputDoc.vue');
    }

    submitAction() {
        return this.respond('Pages/SubmitDoc.vue');
    }

    switchToggleAction() {
        return this.respond('Pages/SwitchToggleDoc.vue');
    }

    tabAction() {
        return this.respond('Pages/TabsDoc.vue');
    }

    textEditorAction() {
        return this.respond('Pages/TextEditorDoc.vue');
    }

    tooltipAction() {
        return this.respond('Pages/TooltipDoc.vue');
    }

    wellAction() {
        return this.respond('Pages/WellDoc.vue');
    }
};
