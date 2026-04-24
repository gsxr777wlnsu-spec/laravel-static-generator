import React, { useState } from 'react';

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

async function readApiResponse(response) {
    const contentType = response.headers.get('content-type') || '';

    if (contentType.includes('application/json')) {
        return response.json();
    }

    return {
        error: `Server returned non-JSON response (HTTP ${response.status})`,
        raw_body: await response.text(),
    };
}

export default function SiteForm({ site = null, onSubmit }) {
    const [formData, setFormData] = useState({
        name: site?.name || '',
        domain: site?.domain || '',
        template_set: site?.template_set || 'base',
        output_path: site?.output_path || '',
        status: site?.status || 'draft',
        locale: site?.locale || 'en',
        sftp_host: site?.sftp_host || '',
        sftp_port: site?.sftp_port || 22,
        sftp_username: site?.sftp_username || '',
        sftp_password: '',
        sftp_auth_method: site?.sftp_auth_method || 'key',
        sftp_remote_path: site?.sftp_remote_path || '',
    });

    const [testing, setTesting] = useState(false);

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const url = site ? `/api/sites/${site.id}` : '/api/sites';
        const method = site ? 'PUT' : 'POST';

        try {
            const response = await fetch(url, {
                method,
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: JSON.stringify(formData),
            });

            const data = await readApiResponse(response);

            if (response.ok) {
                alert('Site saved successfully!');
                window.location.href = '/admin/sites';
            } else {
                alert('Error: ' + JSON.stringify(data.errors || data.error));
            }
        } catch (error) {
            alert('Error: ' + error.message);
        }
    };

    const testConnection = async () => {
        if (!site?.id) {
            alert('Please save the site first before testing connection');
            return;
        }

        setTesting(true);
        try {
            const response = await fetch(`/api/sites/${site.id}/test-sftp`, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            const data = await readApiResponse(response);
            if (response.ok) {
                alert(data.message || 'Connection test completed.');
            } else {
                alert('Error: ' + (data.error || data.message || `Request failed with status ${response.status}`));
            }
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            setTesting(false);
        }
    };

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            <div className="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">Site Information</h3>
                    
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                            <input
                                type="text"
                                name="name"
                                value={formData.name}
                                onChange={handleChange}
                                required
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Domain</label>
                            <input
                                type="text"
                                name="domain"
                                value={formData.domain}
                                onChange={handleChange}
                                required
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Template Set</label>
                            <input
                                type="text"
                                name="template_set"
                                value={formData.template_set}
                                onChange={handleChange}
                                required
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Output Path</label>
                            <input
                                type="text"
                                name="output_path"
                                value={formData.output_path}
                                onChange={handleChange}
                                required
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                            <select
                                name="status"
                                value={formData.status}
                                onChange={handleChange}
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="draft">Draft</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Locale</label>
                            <input
                                type="text"
                                name="locale"
                                value={formData.locale}
                                onChange={handleChange}
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div className="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
                <div className="px-4 py-5 sm:p-6">
                    <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">SFTP Configuration</h3>
                    
                    <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">SFTP Host</label>
                            <input
                                type="text"
                                name="sftp_host"
                                value={formData.sftp_host}
                                onChange={handleChange}
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">SFTP Port</label>
                            <input
                                type="number"
                                name="sftp_port"
                                value={formData.sftp_port}
                                onChange={handleChange}
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                            <input
                                type="text"
                                name="sftp_username"
                                value={formData.sftp_username}
                                onChange={handleChange}
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Auth Method</label>
                            <select
                                name="sftp_auth_method"
                                value={formData.sftp_auth_method}
                                onChange={handleChange}
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="key">SSH Key</option>
                                <option value="password">Password</option>
                            </select>
                        </div>

                        {formData.sftp_auth_method === 'password' && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                                <input
                                    type="password"
                                    name="sftp_password"
                                    value={formData.sftp_password}
                                    onChange={handleChange}
                                    className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                />
                            </div>
                        )}

                        <div className="sm:col-span-2">
                            <label className="block text-sm font-medium text-gray-700 dark:text-gray-300">Remote Path</label>
                            <input
                                type="text"
                                name="sftp_remote_path"
                                value={formData.sftp_remote_path}
                                onChange={handleChange}
                                placeholder="/var/www/site.com"
                                className="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                    </div>

                    {site && (
                        <div className="mt-4">
                            <button
                                type="button"
                                onClick={testConnection}
                                disabled={testing}
                                className="inline-flex items-center rounded-md bg-gray-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-gray-500 disabled:opacity-50"
                            >
                                {testing ? 'Testing...' : 'Test Connection'}
                            </button>
                        </div>
                    )}
                </div>
            </div>

            <div className="flex justify-end space-x-3">
                <a
                    href="/admin/sites"
                    className="inline-flex items-center rounded-md bg-white dark:bg-gray-700 px-3 py-2 text-sm font-semibold text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600"
                >
                    Cancel
                </a>
                <button
                    type="submit"
                    className="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    {site ? 'Update Site' : 'Create Site'}
                </button>
            </div>
        </form>
    );
}
