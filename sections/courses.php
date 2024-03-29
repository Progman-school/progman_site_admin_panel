<?php
if(!isset($_SESSION['authorization'])) {exit();}
$courses = getCourses();
$unusedTechnologies = getUnusedTechnologies();
?>
<div class="edit_panel courses price_list">
    <h3>Courses management</h3>
    <div id="new_price_item">
        <strong>Generate a NEW one</strong>
        <div>
            <input type="text" class="input_adviser" data-table="courses" data-field="name" name="courses__name" value="" maxlength="64" placeholder="Name of the course">
            <br>
            <textarea name="courses__description_en" cols="30" rows="10" maxlength="500" placeholder="Description of the course (en)"></textarea>
            <br>
            <textarea name="courses__description_ru" cols="30" rows="10" maxlength="500" placeholder="Description of the course (ru)"></textarea>
            <br><br>
            <div>
                <label>Level:
                    <select name="courses__level">
                        <?php foreach (COURSE_LEVELS as $level) { ?>
                            <option value="<?= $level ?>"><?= ucfirst($level) ?></option>
                        <?php } ?>
                    </select>
                </label>
                &nbsp;&nbsp;
                <label>Type:
                    <select name="courses__type">
                        <?php foreach (COURSE_TYPES as $type) { ?>
                            <option value="<?= $type ?>"><?= ucfirst($type) ?></option>
                        <?php } ?>
                    </select>
                </label>
            </div>
            <br><br>
        </div>
        <div class="sub_course search_editor">
            <h4>Sub courses:</h4>
            <input
                type="search"
                class="input_adviser"
                placeholder="Name of the sub course"
                data-table="courses"
                data-field="name"
                data-creating=0
                data-child_attributes='{"type": "checkbox", "checked": true, "onclick": "return false;", "value": 1}'
            >
            <button class="add_item" disabled>add</button>
            <div class="checkbox_list"></div>
        </div>
        <div class="technology search_editor">
            <h4>Technologies:</h4>
            <input
                type="search"
                class="input_adviser"
                placeholder="Name of the technology"
                data-table="technologies"
                data-field="name"
                data-creating=1
                data-child_attributes='{"type": "number", "max": 100, "min": 1}'
            >
            <button class="add_item" disabled>add</button>
            <div class="checkbox_list"></div>
        </div>
        <div style="margin-top: 40px">
            <button class="changer" data-task="save" data-api_method="updateCourse" data-id="">CREATE</button>
        </div>
    </div>
    <?php if(!empty($unusedTechnologies)) : ?>
        <details class="unused_technologies">
            <summary><strong>Unused technologies</strong> (<span><?= count($unusedTechnologies) ?></span>pc)</summary>
            <ul data-form_name="user_data">
                <?php foreach ($unusedTechnologies as $item): ?>
                    <li title="<?= $item['description'] ?>">
                        <div>
                            <span><strong><?= $item['name'] ?></strong> (<?= ucfirst($item['type']) ?>)</span>
                            <input type="button" class="technology_remover" value="✖ del" data-id="<?= $item['id'] ?>" title="delete the technology">
                        </div>
                        <div class="sub_info"><?= $item['description'] ? ucfirst($item['description']) : "no description" ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </details>
    <?php else: ?>
        <h6>There are no unused technologies found currently.</h6>
    <?php endif; ?>

    <?php $count = 0; foreach ($courses as $item) { ?>
        <details>
            <summary style="<?= !$item["active"] ? "color: gray; font-style: italic" : "" ?>"><strong><?= $item["name"] ?></strong> course (<?= $item["order"] ?>)</summary>
            <div data-form_name="user_data">
                <div>
                    <span>ID: <strong><?= $item["id"] ?></strong></span>
                    &nbsp;&nbsp;
                    <label>Created: <b><?= date("m/d/Y", strtotime($item["created_at"])) ?></b></label>
                    &nbsp;&nbsp;
                    <span>by <strong><?= $item["admin_login"] ?></strong></span>
                    &nbsp;&nbsp;
                    <label>Last update: <b><?= $item["updated_at"] ? date("m/d/Y H:i", strtotime($item["updated_at"])) : " - " ?></b></label>
                    <textarea name="courses__description_en" cols="30" rows="10" maxlength="500" placeholder="Description of the course (en)" disabled><?= $item["description_en"] ?></textarea>
                    <br>
                    <textarea name="courses__description_ru" cols="30" rows="10" maxlength="500" placeholder="Description of the course (ru)" disabled><?= $item["description_ru"] ?></textarea>
                    <br><br>
                    <div style="display: inline-block">
                        <label>Level:
                            <select name="courses__level" disabled>
                                <?php foreach (COURSE_LEVELS as $level) { ?>
                                    <option value="<?= $level ?>" <?= $level == $item["level"] ? "selected" : "" ?> ><?= ucfirst($level) ?></option>
                                <?php } ?>
                            </select>
                        </label>
                        &nbsp;&nbsp;
                        <label>Type:
                            <select name="courses__type" disabled>
                                <?php foreach (COURSE_TYPES as $type) { ?>
                                    <option value="<?= $type ?>" <?= $type == $item["type"] ? "selected" : "" ?> ><?= ucfirst($type) ?></option>
                                <?php } ?>
                            </select>
                        </label>
                    </div>
                    <div style="display: inline-block">
                        &nbsp;&nbsp;
                        <label>
                            <span style="<?= $item["active"] ? "font-weight: bolder; color: green;" : "" ?>">Active:</span>
                            <input type="checkbox" style="zoom: 1.5;" name="courses__active" value="<?= $item["active"] ?>" <?= $item["active"] ? "checked" : "" ?> onchange="this.value = this.checked" disabled>
                        </label>
                        &nbsp;&nbsp;
                        <label>
                            <span>Order:</span>
                            <input type="number" name="courses__order" value="<?= $item["order"] ?>" disabled>
                        </label>
                    </div>
                    <br><br>
                </div>
                <div class="sub_course search_editor">
                    <h4>Sub courses:</h4>
                    <input
                        type="search"
                        class="input_adviser"
                        placeholder="Name of the sub course"
                        data-table="courses"
                        data-field="name"
                        data-creating=0
                        data-child_attributes='{"type": "checkbox", "checked": true, "onclick": "return false;", "value": 1}'
                        disabled
                    >
                    <button class="add_item" disabled>add</button>
                    <div class="checkbox_list">
                        <?php if ($item['sub_courses']) {
                            foreach ($item['sub_courses'] as $subCourse) { ?>
                                <label title="<?= $subCourse['description_en'] ?? "no description" ?>">
                                    <strong><?= $subCourse['name'] ?></strong> (<?= $subCourse['level'] ?>/<?= $subCourse['type'] ?>)
                                    <input name="courses__<?= $subCourse['id'] ?>" type="checkbox" ="return false;" value="1" data-id="<?= $subCourse['id'] ?>" data-name="<?= $subCourse['name'] ?>" checked disabled>
                                    <input type="hidden" name="courses__<?= $subCourse['id'] ?>_name" value="<?= $subCourse['name'] ?>" >
                                    <input type="hidden" name="courses__<?= $subCourse['id'] ?>_description" value="<?= $subCourse['description_en'] ?>" >
                                    <span class="remover" onclick="this.parentElement.querySelector('input').disabled || this.parentElement.remove()">✖</span>
                                </label>
                            <?php }} else { echo "no sub courses......"; } ?>
                    </div>
                </div>
                <div class="technology search_editor">
                    <h4>Technologies:</h4>
                    <input
                        type="search"
                        class="input_adviser"
                        placeholder="Name of the technology"
                        data-table="technologies"
                        data-field="name"
                        data-creating=1
                        data-child_attributes='{"type": "number", "max": 100, "min": 1}'
                        disabled
                    >
                    <button class="add_item" disabled>add</button>
                    <div class="checkbox_list">
                        <?php foreach ($item['technologies_arr'] as $technologyData) { ?>
                            <label title="<?= $technologyData['descriptions'] ?>">
                                <?= $technologyData['name'] ?>
                                &nbsp;&nbsp;
                                <span class="sub_info"><?= ucfirst($technologyData['type']) ?></span>
                                <input
                                    type="number"
                                    name="technologies__<?= $technologyData['id'] ?>"
                                    value="<?= $technologyData['hours'] ?>"
                                    data-id="<?= $technologyData['id'] ?>"
                                    data-name="<?= $technologyData['name'] ?>"
                                    checked
                                    disabled
                                >
                                <span class="remover" onclick="this.parentElement.querySelector('input').disabled || this.parentElement.remove()">✖</span>
                            </label>
                        <?php } ?>
                    </div>
                </div>
                <div style="margin-top: 40px">
                    <button class="changer" data-task="change" data-api_method="updateCourse" data-id="<?=$item['id'] ?>">CHANGE</button>
                    <button class="deleter" title="delete the course" data-id=<?= $item['id']?> data-api_method="delCourse">del</button>
                </div>
            </div>
        </details>
    <?php } ?>
</div>
