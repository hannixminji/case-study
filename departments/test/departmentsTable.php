<div style="font-family: Arial, sans-serif; margin: 20px; background-color: #f4f4f4;">
    <div style="color: #333; font-size: 24px;">Departments:</div>
    <?php if (empty($departments)): ?>
        <div>No departments found.</div>
    <?php else: ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 20px; background-color: #fff; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);">
            <thead style="background-color: #007bff; color: white;">
                <tr>
                    <th style="padding: 12px; text-align: left; border: 1px solid #dddddd;">ID</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #dddddd;">Name</th>
                    <th style="padding: 12px; text-align: left; border: 1px solid #dddddd;">Department Head ID</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($departments as $department): ?>
                    <tr>
                        <td style="padding: 12px; border: 1px solid #dddddd;"><?= htmlspecialchars($department['id'] ?? '') ?></td>
                        <td style="padding: 12px; border: 1px solid #dddddd;"><?= htmlspecialchars($department['name'] ?? '') ?></td>
                        <td style="padding: 12px; border: 1px solid #dddddd;"><?= htmlspecialchars($department['department_head_id'] ?? '') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
